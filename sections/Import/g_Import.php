<?php
//********************************************************************
// Раздел "Импорт". Таблица.
//********************************************************************


//Проверка сопоставления справочников
//
//  Формат $p_result:
//
//  ['CheckDict']
//    [0]
//      ['DictName']
//      ['DictCode']
//      ['OldValues']
//        ['Name']
//        ['Name']...
//      ['NewValues']
//        ['Name']
//        ['Name']...
//    [1]...
//  ['Import']
//  ['Error']
//
//  Если ['Error'] не пусто, то возникла ошибка и ее текст тут  
//  Если count(['CheckDict'])>0, то найдены новые значения в справочниках, т.е. надо вывести уведомление

namespace Iris\Config\CRM\sections\Import;

use Config;
use Iris\Iris;
use PDO;

class g_Import extends Config
{
    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
            'sections/Import/lib/excel_reader2.php'
        ));
    }

    function getFileName($sysName)
    {
        $storage = Iris::$app->getContainer()->get('storage.service');
        $stream = $storage->getFileStream($sysName);
        $meta = stream_get_meta_data($stream);
        return $meta['uri'];
    }

    function checkDict($params)
    {
        $con = $this->connection;
        $result = array();

        //Откроем xls файл
        list($FileID, $Encoding) = GetFieldValuesByID('Import', $params['recordId'], array('FileID', 'Encoding'), $con);
        $SysName = GetFieldValueByID('File', $FileID, 'File_File', $con);
        $FileName = $this->getFileName($SysName);
        $data = new \Spreadsheet_Excel_Reader($FileName, true, $Encoding);

        //Пройдемся по каждому листу
        $AddDictCount = 0;
        $sheetcount = count($data->boundsheets);
        for ($sheet=0; $sheet<$sheetcount; $sheet++) {

            //Есть ли такой раздел
//		$SectionName = $data->boundsheets[0]['name'];
//		$SectionCode = GetFieldValueByFieldValue('Section', 'Name', $SectionName, 'Code', $con);
//		if (IsEmptyValue($SectionCode)) {
//			break;
//		}

            //Есть ли такая таблица
            $TableName = $data->boundsheets[$sheet]['name'];
            list($TableCode, $TableID) = GetFieldValuesByFieldValue('Table', 'Name', $TableName, array('Code', 'ID'), $con);
            if (IsEmptyValue($TableCode)) {
                $result['Error'] .= json_encode_str('Не найдена таблица "'.$TableName.'"<br/>');
                break;
            }

            //Пройдемся по каждой колонке листа
            $colcount =$this->getRealColCount($data, $sheet);
            for ($col=1; $col<=$colcount; $col++) {

                //Не является ли колонка - колонкой справочника
                $ColName = $data->val(1, $col, $sheet);
                //$DictTableID = GetFieldValueByFieldValue('Table_Column', 'Name', $ColName, 'fkTableID', $con);
                $select_sql = "select fktableid ";
                $select_sql .= "from iris_Table_Column ";
                $select_sql .= "where Name='".$ColName."' and TableID='".$TableID."'";
                $statement = $con->prepare($select_sql);
                $statement->execute();
                $res = $statement->fetchAll();
                //Если ничего не вернули, то ошибка - не нашли колонки
                if (count($res)<=0) {
                    $result['Error'] .= json_encode_str('Не найдена колонка "'.$ColName.'" в таблице "'.$TableName.'" ('.$TableCode.')<br/>');
                    break 2;
                }
                $DictTableID = $res[0]['fktableid'];
                list($DictTableCode, $DictTableName) =
                    GetFieldValuesByFieldValue('Table', 'ID', $DictTableID, array('Code', 'Name'), $con);

                //Если является, то проверим, есть ли новые значения для справочника
                if (!IsEmptyValue($DictTableID)) {

                    //Получим все уникальные значения из колонки xls
                    $rowcount = $data->rowcount($sheet);
                    $xls_values = null;
                    for ($row=2; $row<=$rowcount; $row++) {
                        $xls_values[] = $data->val($row, $col, $sheet);
                    }
                    //Уберем дубликаты
                    $xls_values = array_unique($xls_values);
                    sort($xls_values);

                    //Получим все значения из справочника системы
                    $dict_values = null;
                    //$DictColumnName = 'name'; //TODO: ее надо бы брать из настроек конфигурации
                    $DictColumnName = $this->getDictionaryColumnName($DictTableID);
                    $select_sql = "select ".$DictColumnName." ";
                    $select_sql .= "from ".$DictTableCode." ";
                    $statement = $con->prepare($select_sql);
                    $statement->execute();
                    $res = $statement->fetchAll();
                    foreach ($res as $row) {
                        $dict_values[] = $row[$DictColumnName];
                    }
                    sort($dict_values);

                    //Сравним, есть ли совпадения. Если есть, то добавим их в $p_result.
                    $havenew = false;
                    for ($i=0; $i<count($xls_values); $i++) {
                        if ($xls_values[$i] == '')
                            continue; // miv 16.12.2010: если пустое значение, то пропустим его

                        $isnew = true;
                        for ($j=0; $j<count($dict_values); $j++) {
                            if ($xls_values[$i]==$dict_values[$j]) {
                                $isnew = false;
                                break;
                            }
                        }
                        if ($isnew) {
                            $havenew = true;
                            $result['CheckDict'][$AddDictCount]['NewValues'][]['Name'] = json_encode_str($xls_values[$i]);
                        }
                    }
                    if ($havenew) {
                        for ($j=0; $j<count($dict_values); $j++) {
                            $result['CheckDict'][$AddDictCount]['OldValues'][]['Name'] = json_encode_str($dict_values[$j]);
                        }
                        $result['CheckDict'][$AddDictCount]['DictName'] = json_encode_str($DictTableName);
                        $result['CheckDict'][$AddDictCount]['DictCode'] = $DictTableCode;
                        $result['CheckDict'][$AddDictCount]['DictTableID'] = $DictTableID;
                        $AddDictCount++;
                    }
                }
            }
        }
        return $result;
    }

    //Импорт значений в справочник
    function importDictValues($params)
    {
        $con = $this->connection;
        $result['Result'] = 'ok';

        $Values = json_decode($params['values']);
        list($TableName, $TableCode) = GetFieldValuesByID('Table', $params['tableId'], array('Name', 'Code'), $con);
        global $table_prefix;
        $TableShortCode = substr_replace($TableCode, '', 0, strlen($table_prefix));

        $DictColumnName = $this->getDictionaryColumnName($params['tableId']);

        //Вставка новых значений в справочник
        foreach ($Values as $val) {
            $name = json_decode_str($val->Name);
            $Fields = null;
            //$Fields = FieldValueFormat($DictColumnName, 'Новый', null, $Fields);
            $Fields = FieldValueFormat($DictColumnName, $name, null, $Fields);
            $Fields = FieldValueFormat('ID', create_guid(), null, $Fields);
            if (!InsertRecord($TableShortCode, $Fields['FieldValues'], $con, true)) {
                $result['Error'] = json_encode_str('Ошибка при вставке значения "'.$name.'" в таблицу "'.$TableName.'" ('.$TableCode.').');
                break;
            }
        }

        return $Fields;
    }

    // miv 13.06.2012: для таблицы справочника возвращает название колонки для отображения
    // если у таблицы заполнено поле "Отображать колонку", то при поиске значений будет использоватьс данное поле
    // если поле "Отображать колонку" не заполнено, то будет использоваться стандартная колонка name
    function getDictionaryColumnName($p_dict_table_id) {
        $con = $this->connection;
        $sql = "select case when code is not null then code else 'name' end as column_name from iris_table_column where id = (select showcolumnid from iris_table where id='".$p_dict_table_id."')";
        $data = $con->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $column_name = ($data[0]['column_name'] != '') ? $data[0]['column_name'] : 'name';

        return $column_name;
    }

    //Назначение прав для импортированной записи
    function getAccess($p_ImportID, $p_con=null, $p_result=null)
    {
        $con = $this->connection;
        //Получми права
        $select_sql = "select accessroleid as roleid, contactid as userid, r as r, w as w, d as d, a as a ";
        $select_sql .= "from iris_import_defaultaccess ";
        $select_sql .= "where importid=:p_recordid";
        $query = $con->prepare($select_sql);
        $query->bindParam(':p_recordid', $p_ImportID);
        $query->execute();
        $permissions = $query->fetchAll(PDO::FETCH_ASSOC);
        return $permissions;
    }

    //Получить список колонок для проверки дубликатов
    function getDuplicateColumns($p_ImportID, $p_TableID, $p_con=null)
    {
        $con = $this->connection;

        //Получми список названий колонок
        $select_sql = "select c.Name as name, c.Code as code ";
        $select_sql .= "from iris_import_duplicate d left join iris_Table_Column c on d.ColumnID=c.ID ";
        $select_sql .= "where d.importid=:p_recordid and d.tableid=:p_tableid";
        $query = $con->prepare($select_sql);
        $query->bindParam(':p_recordid', $p_ImportID);
        $query->bindParam(':p_tableid', $p_TableID);
        $query->execute();
        $res = $query->fetchAll();

        //Сформируем результат
        foreach ($res as $row) {
            $transport = FieldValueFormat($row['code'], '', '', $transport);
        }

        return $transport;
    }

    //Получение списка дублирующихся значений
    function getDuplicates($p_TableCode, $p_Columns, $p_logic_operator, $p_Values, $p_con=null)
    {
        $con = $this->connection;
        if ($p_logic_operator == '') {
            $p_logic_operator = 'or';
        }

        //Заполним значения в columns
        foreach ($p_Columns['FieldValues'] as &$cval) {
            foreach ($p_Values['FieldValues'] as $vval) {
                if ($cval['Name'] == $vval['Name']) {
                    $cval['Value'] = $vval['Value'];
                }
            }
        }

        //Получми список названий колонок
        $select_sql = "select ";
        for ($i=0; $i<count($p_Values['FieldValues']); $i++) {
            $select_sql .= $i==0 ? '' : ", ";
            $select_sql .= $p_Values['FieldValues'][$i]['Name'];
        }
        $select_sql .= " from ".$p_TableCode." ";
        $select_sql .= "where ";
        for ($i=0; $i<count($p_Columns['FieldValues']); $i++) {
            //$select_sql .= $i==0 ? '' : " or ";
            $select_sql .= $i==0 ? '' : " ".$p_logic_operator." ";
            $select_sql .= "(".$p_Columns['FieldValues'][$i]['Name']."=:p_".$p_Columns['FieldValues'][$i]['Name'].")";
        }
        $query = $con->prepare($select_sql);
        for ($i=0; $i<count($p_Columns['FieldValues']); $i++) {
            $query->bindParam(':p_'.$p_Columns['FieldValues'][$i]['Name'], $p_Columns['FieldValues'][$i]['Value']);
        }
        $query->execute();
        $res = $query->fetchAll();

        //Сформируем результат
        for ($i=0; $i<count($res); $i++) {
            for ($j=0; $j<count($p_Values['FieldValues']); $j++) {
                $transport[$i] = FieldValueFormat($p_Values['FieldValues'][$j]['Name'], $res[$i][$p_Values['FieldValues'][$j]['Name']], null, $transport[$i]);
            }
        }

        return $transport;
    }

    //Импорт таблицы
    function importSheet($p_data, $p_sheet, $p_import_id, $p_duplicate1=0, $p_duplicate2=0, $p_con=null, $p_result=null)
    {
        $con = $this->connection;
        $sheet = $p_sheet;
        $data = $p_data;

        //Есть ли такая таблица
        $TableName = $data->boundsheets[$sheet]['name'];
        list($TableCode, $TableID) = GetFieldValuesByFieldValue('Table', 'Name', $TableName, array('Code', 'ID'), $con);
        if (IsEmptyValue($TableCode)) {
            $p_result['Error'] .= json_encode_str('Не найдена таблица "'.$TableName.'"<br/>');
            return $p_result;
        }


        //Пройдемся по каждой колонке листа и получим список названий колонок в БД (кладем в $Fields)
        global $table_prefix;
        $Fields = null;
        $colcount = $this->getRealColCount($data, $sheet);
        if ($colcount>0) {
            $Fields = FieldValueFormat('id', null, null, $Fields);
        }
        $dict_tables_ids = array(); // miv 13.06.2012: для хранения id таблиц-справочников
        for ($col=1; $col<=$colcount; $col++) {

            //Не является ли колонка - колонкой справочника
            $ColName = $data->val(1, $col, $sheet);
            //$DictTableID = GetFieldValueByFieldValue('Table_Column', 'Name', $ColName, 'fkTableID', $con);
            $select_sql = "select fktableid, code ";
            $select_sql .= "from iris_Table_Column ";
            $select_sql .= "where Name='".$ColName."' and TableID='".$TableID."'";
            $statement = $con->prepare($select_sql);
            $statement->execute();
            $res = $statement->fetchAll();
            //Если ничего не вернули, то ошибка - не нашли колонки
            if (count($res)<=0) {
                $p_result['Error'] .= json_encode_str('Не найдена колонка "'.$ColName.'" в таблице "'.$TableName.'" ('.$TableCode.')<br/>');
                return $p_result;
            }
            $DictTableID = $res[0]['fktableid'];
            $dict_tables_ids[] = $DictTableID; // miv 13.06.2012
            $ColCode = $res[0]['code'];
            list($DictTableCode, $DictTableName) =
                GetFieldValuesByFieldValue('Table', 'ID', $DictTableID, array('Code', 'Name'), $con);

            //Формируем список колонок, куда будем подставлять потом значения
            //Усли Caption не пусто, то будем потом встравлять id справочника
            $TableShortCode = substr_replace($DictTableCode, '', 0, strlen($table_prefix));
            $Fields = FieldValueFormat($ColCode, null, $TableShortCode, $Fields);
        }


        //Получим права доступа
        $permissions = $this->getAccess($p_import_id, $con);

        //Получим список колонок-дубликатов
        $duplicate_columns = $this->getDuplicateColumns($p_import_id, $TableID, $con);


        //Теперь пройдемся по каждой строчке и импортнем ее
        $rowcount = $data->rowcount($sheet);
        for ($row=2; $row<=$rowcount; $row++) {

            //Сформируем значения
            $Fields['FieldValues'][0]['Value'] = create_guid();
            for ($col=1; $col<=$colcount; $col++) {
                //Если не справочник, то все просто
                if (IsEmptyValue($Fields['FieldValues'][$col]['Caption'])) {
                    $Fields['FieldValues'][$col]['Value'] = $data->val($row, $col, $sheet);
                }
                //Если справочник, то получим id записи
                else {
                    $DictColumnName = $this->getDictionaryColumnName($dict_tables_ids[$col-1]);
                    $dict_rec_id = GetFieldValueByFieldValue(
                    //$Fields['FieldValues'][$col]['Caption'], 'Name',
                        $Fields['FieldValues'][$col]['Caption'], $DictColumnName,
                        $data->val($row, $col, $sheet), 'ID', $con);
                    $dict_rec_id = IsEmptyValue($dict_rec_id) ? null : $dict_rec_id;
                    $Fields['FieldValues'][$col]['Value'] = $dict_rec_id;
                }
            }


            //Проверим на дубликаты
            //Найдем дубликаты
            $logic_operator_sql = "select isduplicateandoperator as op_flag from iris_import where id=:import_id";
            $logic_operator_cmd = $con->prepare($logic_operator_sql);
            $logic_operator_cmd->execute(array(":import_id" => $p_import_id));
            $logic_operator_data = $logic_operator_cmd->fetchAll(PDO::FETCH_ASSOC);
            $logic_operator = $logic_operator_data[0]['op_flag'] == 0 ? 'or' : 'and';

            $duplicates = $this->getDuplicates($TableCode, $duplicate_columns, $logic_operator, $Fields, $con);
//		print_r($duplicates);
//		return 1;
            //Как с ними поступать
            //0 - не импортировать
            //1 - обновить только пустые значения
            //2 - перезаписывать
            //3 - добавлять дубликат
            $duplicate_rule = 3;
            //Если дубликат 1
            if (count($duplicates)==1) {
                $duplicate_rule = $p_duplicate1;
            }
            else
                //Если дубликатов больше 1
                if (count($duplicates)>1) {
                    $duplicate_rule = $p_duplicate2;
                }
                //Если дубликаты не найдены
                else {
                }

            $TableShortCode = substr_replace($TableCode, '', 0, strlen($table_prefix));

            //1 - обновить только пустые значения
            if ($duplicate_rule == 1) {
                //Обновим
                for ($i=0; $i<count($duplicates); $i++) {
                    //сформируем список значений, исключив id (номер 0) и значения, которые уже заполнены в БД
                    $UpdateFields = null;
                    $id = $duplicates[$i]['FieldValues'][0]['Value'];
                    for ($f=1; $f<count($Fields['FieldValues']); $f++) {
                        for ($j=0; $j<count($duplicates[$i]['FieldValues']); $j++) {
                            if ($Fields['FieldValues'][$f]['Name'] == $duplicates[$i]['FieldValues'][$j]['Name']) {
                                if (IsEmptyValue($duplicates[$i]['FieldValues'][$j]['Value'])) {
                                    $UpdateFields[] = $Fields['FieldValues'][$f];
                                }
                                break;
                            }
                        }
                    }
                    if (!UpdateRecord($TableShortCode, $UpdateFields, $id, $con)) {
                        $values = '';
                        foreach ($UpdateFields as $val) {
                            $values .= $val['Value'].'; ';
                        }
                        $update_result = $con->errorInfo();
                        $p_result['Error'] = json_encode_str('Ошибка при обновлении строки '.$row.': "'.$values.'" в таблице "'.$TableName.'" ('.$TableCode.'): '.$update_result[2]);
                        return $p_result;
                    }
                }
            }
            else
                //2 - перезаписывать
                if ($duplicate_rule == 2) {
                    //сформируем список значений, исключив id (номер 0)
                    $UpdateFields = null;
                    for ($i=1; $i<count($Fields['FieldValues']); $i++) {
//				if (!IsEmptyValue($Fields['FieldValues'][$i]['Value'])) {
                        $UpdateFields[] = $Fields['FieldValues'][$i];
//				}
                    }
                    //Обновим
                    for ($i=0; $i<count($duplicates); $i++) {
                        $id = $duplicates[$i]['FieldValues'][0]['Value'];
                        //echo $id.'.';
                        //print_r($UpdateFields);
                        if (!UpdateRecord($TableShortCode, $UpdateFields, $id, $con)) {
                            $values = '';
                            foreach ($UpdateFields as $val) {
                                $values .= $val['Value'].'; ';
                            }
                            $update_result = $con->errorInfo();
                            $p_result['Error'] = json_encode_str('Ошибка при обновлении строки '.$row.': "'.$values.'" в таблице "'.$TableName.'" ('.$TableCode.'): '.$update_result[2]);
                            return $p_result;
                        }
                    }
                }
                else
                    //3 - добавлять дубликат (если не найден дубликат, то тоже сюда)
                    if ($duplicate_rule == 3) {
                        //Вставим строку
                        //unconvert не нужен, т.к. value присвоено вручную в cp1251
                        if (!InsertRecord($TableShortCode, $Fields['FieldValues'], $con)) {
                            $values = '';
                            foreach ($Fields['FieldValues'] as $val) {
                                $values .= $val['Value'].'; ';
                            }
                            $insert_result = $con->errorInfo();
                            $p_result['Error'] = json_encode_str('Ошибка при вставке строки '.$row.': "'.$values.'" в таблицу "'.$TableName.'" ('.$TableCode.'): '.$insert_result[2]);
                            return $p_result;
                        }

                        //Добавим к ней права доступа
                        ChangeRecordPermissions($TableCode, $Fields['FieldValues'][0]['Value'], $permissions, $con);
                    }
        }

        $p_result['Result'] = json_encode_str('ok');

        return $p_result;
    }

    function startImport($params)
    {
        $con = $this->connection;

        //Откроем xls файл и Получим правила работы с дублями
        list($FileID, $Encoding, $Duplicate1, $Duplicate2) = GetFieldValuesByID('Import', $params['recordId'],
            array('FileID', 'Encoding', 'Duplicate1', 'Duplicate2'), $con);
        $SysName = GetFieldValueByID('File', $FileID, 'File_File', $con);
        $FileName = $this->getFileName($SysName);
        $data = new \Spreadsheet_Excel_Reader($FileName, true, $Encoding);


        //Пройдемся по каждому листу
        $AddDictCount = 0;
        $sheetcount = count($data->boundsheets);
        for ($sheet=0; $sheet<$sheetcount; $sheet++) {
            $result = $this->importSheet($data, $sheet, $params['recordId'], $Duplicate1, $Duplicate2, $con, $result);
        }

        return $result;
    }

    // miv 19.08.2010: Определяет реальное число колонок листа. Если лист содержит пустые колонки в первой строке, то считаем, что на первой такой колонке документ закончен
    function getRealColCount($p_data, $p_sheet) {
        $colcount = $p_data->colcount($p_sheet);
        for ($col=1; $col<=$colcount; $col++) {
            $ColName = $p_data->val(1, $col, $p_sheet);
            if ($ColName == '')
                return $col-1;
        }
        return $colcount;
    }
}
?>
