<?php

//**********************************************************************
// Функции для построения отчетов
//**********************************************************************

use Iris\Iris;

include_once Iris::$app->getCoreDir() . 'core/engine/printform.php';

/**
 * @deprecated Использовалась при формировании отчетов, теперь там используется стандартный fputcsv()
 */
function array_to_csv($list, $d = ";", $q = '"')
{
    $line = "";
    foreach ($list as $field) {
        $field = iris_str_replace("\r\n", "\n", $field);
        if (iris_preg_match("/[$d$q\\n\\r]/", $field)) {
            $field = $q . iris_str_replace($q, $q . $q, $field) . $q;
        }
        $line .= $field . $d;
    }
    $line = iris_substr($line, 0, -1);
    $line .= "\n";

    return $line;
}

/*
//Построить список параметров в виде input полей (для передачи их в новый отчёт по ссылке)
function BuildReportParameters($p_parameters)
{
  $parameters = '';
  foreach ($p_parameters as $key => $value) {
    $parameters .= "<input type=\"hidden\" id=\"$key\" value=\"$value\">";
  }
  return $parameters;
}
*/

//Получить фильтры, которые отмечены для отображения (для окна с фильтрами)
function Report_GetFilters($p_reportid, $p_report_code, $in_report = false, $p_filters = [])
{
    $html_params = '';

    $con = GetConnection();
    if (($p_reportid != '') and ($p_report_code != '')) {
        $p_reportid = '';
    } // если указали оба параметр, то берем отчет по коду

    //Получим все фильтры для отображения
    $select_sql = '(select rf.ID as id, rt.Code as tablealias, tc.Code as columncode, ct.SystemCode as columntypecode, ' .
        'rf.StringValue as stringvalue, rf.IntValue as intvalue, rf.FloatValue as floatvalue, ' .
        _db_datetime_to_string('rf.DateValue') . ' as datetimevalue, ' .
        _db_date_to_string('rf.DateValue') . ' as datevalue, rf.GUIDValue as guidvalue, ' .
        'rf.Name as caption, rf.Condition as condition, 1 as showcondition, ' .
        'tc.id as tablecolumnid, rf.parentfilterid as parentfilterid, ' .
        't.Code as tablecode, t.is_access as isaccess, rf.code as filtercode, ' .
        'rf.number as number, 1 as num1, rf.droplistsql as droplistsql, equation as equation, rf.code as paramname ' .
        'from iris_Report r ' .
        'left join iris_Report_Filter rf on rf.ReportID=r.ID ' .
        'left join iris_Report_Table rt on rt.ID=rf.Report_TableID ' .
        'left join iris_Table_Column tc on tc.ID=rf.ColumnID ' .
        'left join iris_Table t on tc.fkTableID=t.ID ' .
        'left join iris_ColumnType ct on ct.ID=tc.ColumnTypeID ' .
        //'where r.id=:p_reportid '.
        //'where (r.id=:p_reportid or r.code=:p_reportcode) '.
        'where (r.id=:p_reportid or (r.code=:p_reportcode and :p_reportcode <> \'\')) ' .
        "and rf.isvisible='1') " .
        'union ' .
        "(select rp.ID as id, t.Code as tablealias, '' as columncode, ct.SystemCode as columntypecode, " .
        'rp.StringValue as stringvalue, rp.IntValue as intvalue, rp.FloatValue as floatvalue, ' .
        _db_datetime_to_string('rp.DateValue') . ' as datetimevalue, ' .
        _db_date_to_string('rp.DateValue') . ' as datevalue, rp.GUIDValue as guidvalue, ' .
        "rp.Name as caption, '0' as condition, 0 as showcondition, " .
        'null as tablecolumnid, null as parentfilterid, ' .
        't.Code as tablecode, t.is_access as isaccess, rp.code as filtercode, ' .
        'rp.number as number, 2 as num1, rp.droplistsql as droplistsql, equation as equation, rp.code as paramname ' .
        'from iris_Report_Parameter rp ' .
        'left join iris_Table t on t.ID=rp.TableID ' .
        'left join iris_ColumnType ct on ct.ID=rp.TypeID ' .
        //'where rp.reportid=:p_reportid) '.
        //'where (rp.reportid in (select id from iris_report where id=:p_reportid or code=:p_reportcode)) ) '.
        'where (rp.reportid in (select id from iris_report where id=:p_reportid or (code=:p_reportcode and :p_reportcode <> \'\'))) ' .
        "and rp.isvisible='1') " .
        "order by num1, number";
    $statement = $con->prepare($select_sql, [PDO::ATTR_EMULATE_PREPARES => true]);
    $statement->bindParam(':p_reportid', $p_reportid);
    $statement->bindParam(':p_reportcode', $p_report_code);
    $statement->execute();
    $res = $statement->fetchAll();

    //Пройдемся по каждому фильтру и нарисуем его
    $result = '<form parameters=[#parameters#] reportid="' . $p_reportid . '">' .
        '<div style="max-height: 200px; overflow-y: auto"><table class="form_table"><tbody>';
    $parameters = [];
    $param_number = 0; //для нумерации параметров
//print_r($p_filters);echo '<br>';

//foreach ($p_filters as $filter) {
//echo '<br>'.json_decode_str($filter->Name).': '.json_decode_str($filter->Value).'';
//}

    //Если передан хотя бы 1 параметр, то значения по умолчанию не брать.
    $userparams = false;
    if ($p_filters) {
        foreach ($p_filters as $filter) {
            if (!IsEmptyValue($filter->Value)) {
                $userparams = true;
                break;
            }
        }
    }

    foreach ($res as $row) {
        $result .= '<tr class="form_row">';

        //Название фильтра
        $result .= '<td class="form_table" width="5%" style="white-space: nowrap;">';
        if ($row['parentfilterid']) {
            $result .= '&nbsp;&nbsp;';
        }
        $result .= htmlspecialchars(json_encode_str($row['caption']));
        $result .= '</td>';

        $param_name = $row['filtercode'] ? $row['filtercode'] : 'p_autoparam_' . $param_number;
        //Если уже заполнены параметры, то получим значение параметра из переданных параметров
        $param_value = null;
        $condition = $row['condition'];
        $isMultiple = ($condition == 7);
        foreach ($p_filters as $filter) {
            $param_value = null;
            if ($filter->ParameterID == $row['id'] || $filter->FilterID == $row['id']) {
                $param_value = json_decode_str($filter->Value);
                $condition = json_decode_str($filter->Condition);
            }
            else //      if (IsEmptyValue($filter->ParameterID) && IsEmptyValue($filter->FilterID) && $filter->ParameterName == $row['paramname']) {
            {
                if ($filter->ParameterName == $row['paramname']) {
                    $param_value = json_decode_str($filter->Value);
                    $condition = json_decode_str($filter->Condition);
                }
            }

            //Если параметр передан и он есть в отчёте, то отобразим его на странице отчёта.
            if (!IsEmptyValue($param_value)) {
                $html_params .=
                    '<tr>' .
                    '<td style="white-space: nowrap;">' . json_decode_str($filter->Name) . ':</td>' .
                    '<td style="white-space: nowrap;">' . htmlspecialchars(json_decode_str($filter->Caption)) . '</td>' .
                    '</tr>';
                break;
            }
        }


        //Условие
        $result .= '<td class="form_table" width="80">';
        $display = $row['columntypecode'] && $row['showcondition'] ? '' : 'display: none;';
        $result .=
            '<select filterid="' . (1 == $row['num1'] ? $row['id'] : '') . '" ' .
            'parameterid="' . (2 == $row['num1'] ? $row['id'] : '') . '" mandatory="no" ' .
            ($isMultiple ? 'disabled="disabled"' : '') .
            'class="edtText_selected" style="width: 100%;' . $display . '" id="c_' . $param_name . '" ' .
            'onfocus="this.className=\'edtText_selected\';" onblur="this.className=\'edtText\';" ' .
            'elem_type="select">' .
            '<option  value="0"></option>' .
            '<option ' . (1 == $condition ? 'selected=""' : '') . ' value="1">=</option>' .
            '<option ' . (2 == $condition ? 'selected=""' : '') . ' value="2">&gt;</option>' .
            '<option ' . (3 == $condition ? 'selected=""' : '') . ' value="3">&gt;=</option>' .
            '<option ' . (4 == $condition ? 'selected=""' : '') . ' value="4">&lt;</option>' .
            '<option ' . (5 == $condition ? 'selected=""' : '') . ' value="5">&lt;=</option>' .
            '<option ' . (6 == $condition ? 'selected=""' : '') . ' value="6">!=</option>' .
            '<option ' . (7 == $condition ? 'selected=""' : '') . ' value="7">одно из</option>' .
            '</select>';
        $result .= '</td>';

        //Разделитель
        //$result .= '<td style="width: 10px;">';
        //$result .= '&nbsp;';
        //$result .= '</td>';

        //Значение
        $result .= '<td class="form_table">';
        $elem_html = '';

        if (!$in_report && $row['equation'] && $row['columntypecode'] != 'guid') {
            $param_value = $param_value || $userparams ? $param_value : FillFormFromText($row['equation'], 'Report', $p_reportid);
            $elem_html .= GetElementHTML($param_name, $row['columntypecode'], json_encode_str($param_value));
        }
        else {
            switch ($row['columntypecode']) {
                case 'string':
                case 'char':
                case 'text':
                    $param_value = $param_value || $userparams ? $param_value : $row['stringvalue'];
                    $elem_html .= GetElementHTML($param_name, $row['columntypecode'], json_encode_str($param_value));
                    break;
                case 'date':
                    $param_value = $param_value || $userparams ? $param_value : $row['datevalue'];
                    $elem_html .= GetElementHTML($param_name, $row['columntypecode'], $param_value);
                    break;
                case 'datetime':
                    $param_value = $param_value || $userparams ? $param_value : $row['datetimevalue'];
                    $elem_html .= GetElementHTML($param_name, $row['columntypecode'], $param_value);
                    break;
                case 'int':
                    $param_value = $param_value || $userparams ? $param_value : $row['intvalue'];
                    $elem_html .= GetElementHTML($param_name, $row['columntypecode'], $param_value);
                    break;
                case 'float':
                    $param_value = $param_value || $userparams ? $param_value : $row['floatvalue'];
                    $elem_html .= GetElementHTML($param_name, $row['columntypecode'], $param_value);
                    break;
                case 'guid':
                    $param_value = $param_value || $userparams ? $param_value : $row['guidvalue'];

                    //Название типа колонки
                    if ('guid' == $row['columntypecode']) { //формальность
                        // miv 13.07.2011: теперь источник грида определяется достоверно - по параметрам, указанным в iris_table
                        // (до этого имя раздела и справочника определялось от названия таблицы, вырезая iris_. Разделом считалась таблица, у которой учитывались права доступа по записям)
                        $sql = "select T0.code as table_code, case when T1.code is not null then 'grid' else 'dict' end as source_type_value, case when T1.code is not null then T1.code else T0.dictionary end as source_name_value ";
                        $sql .= "from iris_table T0 left join iris_section T1 on T0.sectionid = T1.id ";
                        $sql .= "where T0.code=:tablecode";
                        $cmd = $con->prepare($sql);
                        $cmd->execute([":tablecode" => $row['tablecode']]);
                        $res = current($cmd->fetchAll(PDO::FETCH_ASSOC));

                        //Если колонка для отображения отличается от name
                        $select_sql = 'select tc1.code as columnname ' .
                            'from iris_table t1 ' .
                            'left join iris_table_column tc1 on tc1.id = t1.ShowColumnID ' .
                            'where t1.code = :p_tablecode ';
                        $statement = $con->prepare($select_sql);
                        $statement->execute([':p_tablecode' => $row['tablecode']]);
                        $column_res = $statement->fetch();
                        $displaycolumnname = IsEmptyValue($column_res['columnname']) ? 'name' : $column_res['columnname'];

                        //Получим caption
                        if ($row['tablecode']) {
                            $sql = "select " . $displaycolumnname . " as name from " . $row['tablecode'] . " where ID = :p_id";
                            $statement = $con->prepare($sql);
                            $statement->execute([':p_id' => $param_value]);
                            $caption_res = $statement->fetch();
                        }

                        /*
            if ($row['isaccess']) {
              $elem_html .= GetElementHTML($param_name, $row['columntypecode'], $param_value, json_encode_str($caption_res['name']), ucfirst(substr($row['tablecode'], 5)), 'grid', 'lookup');
            }
            else {
              //TODO: Сделать дополнительно проверку в xml
              $elem_html .= GetElementHTML($param_name, $row['columntypecode'], $param_value, json_encode_str($caption_res['name']), substr($row['tablecode'], 5), 'dict', 'lookup');
            }
            */
                        $elem_html .= GetElementHTML($param_name, $row['columntypecode'], $param_value, json_encode_str(!empty($caption_res['name']) ? $caption_res['name'] : null), $res['source_name_value'], $res['source_type_value'], 'lookup', 'no', null, $displaycolumnname);
                    }
                    break;
            }
        }
        if ($row['droplistsql'] != '') {
            $elem_html = GetElementHTML($param_name, $row['columntypecode'], $param_value, json_encode_str($caption_res['name']), '', '', 'select', 'no', ["droplistsql" => $row['droplistsql'], "isMultiple" => $isMultiple]);
        }
        $result .= $elem_html;

        //if ($row['columntypecode']) {
        $parameters[] = $param_name;
        //}

        $result .= '</td>';
        $result .= '</tr>';
        $param_number++;
    }
    $result .= '</tbody></table></div>';

    $result = iris_str_replace('[#parameters#]', json_encode($parameters), $result);

    //Кнопки
    $result .=
        '<table class="form_table_buttons_panel"><tbody><tr><td style="vertical-align: middle;"/>' .
        ($in_report ? '<td><input type="button" onclick="jQuery(\'#window_filter_report\').hide(); jQuery(\'#filters\').hide();" value="' . json_encode_str('Скрыть') . '" style="width: 90px;" class="button" id="btn_hide"/></td>' : '') .
        '<td align="right">' .
        ($in_report ? '<input type="button" value="' . json_encode_str('*.csv') . '" style="width: 90px;" class="button" id="btn_csv" ' . ($in_report ? 'onclick="redraw_report(\'csv\');"' : '') . '/>' : '') .
        '<input type="button" value="' . json_encode_str($in_report ? 'Обновить' : 'Запуск') . '" style="width: 90px;" class="button" id="btn_ok" ' . ($in_report ? 'onclick="redraw_report();"' : '') . '/>' .
        ($in_report ? '' : '<input type="button" onclick="Windows.close(get_window_id(this));" value="' . json_encode_str('Отмена') . '" style="width: 90px;" class="button" id="btn_cancel"/>') .
        '</td></tr></tbody></table>';

    $result .= '</form>';

    $result_new['Show'] = 0 != $param_number;
    $result_new['Card'] = $result;


    if ($html_params) {
        $html_params =
            //'<h2>Фильтры</h2>'.
            '<table><tbody>' .
            $html_params .
            '</tbody></table>';
    }

    return [$result_new, $html_params];
}


function GetElementHTML($p_name, $p_type = 'string', $p_value = null, $p_caption = 'null', $p_sourcename = '', $p_sourcetype = '', $p_elem_type = 'text', $p_mandatory = 'no', $p_addl_params = null, $displaycolumnname = 'Name')
{
    $result = '';
    $p_value = htmlspecialchars($p_value);
    $p_type = htmlspecialchars($p_type);
    $p_caption = htmlspecialchars($p_caption);
    switch ($p_type) {

        case 'string':
        case 'int':
            $result .= '<table style="width: 100%; table-layout: fixed;"><tbody><tr><td>';
            $result .= '<input id="' . $p_name . '" class="edtText" type="text" autocomplete="off" elem_type="' . $p_elem_type . '" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="' . $p_value . '" mandatory="' . $p_mandatory . '" style="width: 100%;"/>';
            $result .= '</td></tr></tbody></table>';
            $result .= '<input id="#' . $p_name . '" type="hidden" value="' . $p_type . '"/>';
            break;

        case 'float':
            $result .= '<table style="width: 100%; table-layout: fixed;"><tbody><tr><td>';
            $result .= '<input id="' . $p_name . '" class="edtText" type="text" autocomplete="off" elem_type="' . $p_elem_type . '" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="' . $p_value . '" mandatory="' . $p_mandatory . '" style="width: 100%;"/>';
            $result .= '</td></tr></tbody></table>';
            $result .= '<input id="#' . $p_name . '" type="hidden" value="decimal"/>';
            break;

        case 'date':
            $result .= '<table cellspacing="0" width="100%"><tbody><tr><td>';
            $result .= '<input id="' . $p_name . '" class="edtText" type="text" autocomplete="off" elem_type="' . $p_elem_type . '" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="' . $p_value . '" mandatory="' . $p_mandatory . '" maxlength="16" style="width: 100%;"/>';
            $result .= '</td><td width="20">';
            $result .= '<div class="calendar_img" onclick="new CalendarDateSelect($(this).parentNode.parentNode.getElementsByTagName(\'input\')[0], {time: false, buttons: true, embedded: false, year_range: 10} );" />';
            $result .= '</td></tr></tbody></table>';
            $result .= '<input id="#' . $p_name . '" type="hidden" value="datetime"/>';
            break;
        case 'datetime':
            $result .= '<table cellspacing="0" width="100%"><tbody><tr><td>';
            $result .= '<input id="' . $p_name . '" class="edtText" type="text" autocomplete="off" elem_type="' . $p_elem_type . '" onblur="this.className=\'edtText\';" onfocus="this.className=\'edtText_selected\';" value="' . $p_value . '" mandatory="' . $p_mandatory . '" maxlength="16" style="width: 100%;"/>';
            $result .= '</td><td width="20">';
            $result .= '<div class="calendar_img" onclick="new CalendarDateSelect($(this).parentNode.parentNode.getElementsByTagName(\'input\')[0], {time: true, buttons: true, embedded: false, year_range: 10} );" />';
            $result .= '</td></tr></tbody></table>';
            $result .= '<input id="#' . $p_name . '" type="hidden" value="datetime"/>';
            break;

        case 'guid':
            if ('lookup' == $p_elem_type) {
                $result .= '<table cellspacing="0" width="100%"><tbody><tr><td>';
                $result .= '<input id="' . $p_name . '" class="edtText" type="text" elem_type="' . $p_elem_type . '" onblur="this.className=\'edtText\'; ' .
                    'TryToCloseAutoCompleteElem(this);" onfocus="this.className=\'edtText_selected\';" ' .
                    'onkeyup="DrawAutoComplete(this, event);" original_value="' . $p_caption . '" value="' . $p_caption . '" lookup_value="' . $p_value . '" ' .
                    'lookup_column="' . $displaycolumnname . '" lookup_grid_source_name="' . $p_sourcename . '" lookup_grid_source_type="' . $p_sourcetype . '" ' .
                    'autocomplete="off" is_lookup="Y" style="width: 100%;" mandatory="' . $p_mandatory . '"/>';
                $result .= '</td><td width="20">';
                $result .= '<input id="' . $p_name . '_btn" class="button" type="button" onclick="openlookupwindow(this);" value="..." style="margin: 0px 0px 0px 1px; width: 20px;" />';
                $result .= '</td></tr></tbody></table>';
                $result .= '<input id="#' . $p_name . '" type="hidden" value="id"/>';
            }
            if ('select' == $p_elem_type) {
                //TODO: для select сделать заполнение options
                //$result .= '';
            }
            break;
    }

    // если указано условие для выпадающего списка, то данный элемент, вне зависимости от типа, будет выпадающим списком
    if (($p_elem_type == 'select') and ($p_addl_params['droplistsql'] != '')) {
        $result = '<table cellspacing="0" width="100%"><tbody><tr><td>';
        $result .= '<select mandatory="no" class="edtText" ';
        $result .= 'style="width:100%;' . ($p_addl_params['isMultiple'] ? ' display:none' : '') . '" id="' . $p_name . '" ';
        $result .= ($p_addl_params['isMultiple'] ? ' multiple="multiple" ' : '');
        $result .= 'onfocus="this.className = \'edtText_selected\';" onblur="this.className = \'edtText\';" elem_type="select">';
        $result .= '<option value=""></option>';
        // $selectedValues = $p_addl_params['isMultiple'] ?
        $selectedValues = isJSON(htmlspecialchars_decode($p_value)) ?
            json_decode(htmlspecialchars_decode($p_value), true) : [$p_value];
        if (!$selectedValues) {
            $selectedValues = [];
        }
        $con = db_connect();
        $query = $con->query($p_addl_params['droplistsql']);
        if ($query != null) {
            $options = $query->fetchAll(PDO::FETCH_ASSOC);
        }
        else {
            $options = [["value" => "", "caption" => "dropdown list sql has error"]];
        }
        foreach ($options as $option) {
            $result .= '<option ' . (in_array($option['value'], $selectedValues) ? 'selected' : '') . ' value="' . EscapeSelectValue($option['value']) . '">' . json_encode_str($option['caption']) . '</option>';
        }
        $result .= '</select>';
        $result .= '</td></tr></tbody></table>';
        $result .= '<input id="#' . $p_name . '" type="hidden" value="datetime"/>';
    }

    return $result;
}

function EscapeSelectValue($p_value)
{
    return str_replace(['"', "<", ">", "&"], ["&quot;", "&lt;", "&gt;", "&amp;"], json_encode_str($p_value));
}

function isJSON($json_string)
{
    // http://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php/6041857#6041857
    // http://www.ietf.org/rfc/rfc4627.txt
    return !preg_match('/[^,:{}\\[\\]0-9.\\-+Eaeflnr-u \\n\\r\\t]/',
        preg_replace('/"(\\.|[^"\\\\])*"/', '', $json_string));
}


//Построение sql отчета
function BuildReportSQL($p_reportid, $p_filters)
{
//echo '<pre>'; print_r($p_filters); echo '</pre>';
    $con = GetConnection();

    $filters_array = null;

    //Строим текст sql запроса
    $select = 'select ';
    $from = 'from ';
    $where = '';
    $order = '';
    $group = '';
    $limit = '';

    //Секция LIMIT
    list($recordcount, $distinct) = GetFieldValuesByID('Report', $p_reportid, ['RecordCount', 'isdistinct'], $con);
    if ($recordcount) {
        $limit = ' LIMIT ' . $recordcount;
    }
    if ($distinct) {
        $select .= 'DISTINCT ';
    }

    //Получим список колонок (секция SELECT)
    $select_sql = 'select rt.Code as tablealias, tc.Code as columncode, rc.Code as columnalias, ' .
        'rc.OrderNumber as ordernumber, rc.OrderDirection as orderdirection, ' .
        'rc.GroupNumber as groupnumber, rc.grouptype as grouptype, ' .
        'af.Code as functioncode, aft.Code as totalcode, ' .
        'rc.ShowInReport as showinreport, rc.ShowInGraph as showingraph, rc.Name as showname, ' .
        'r.xcolumnid as xcolumnid, tc.id as columnid, ct.SystemCode as columntypecode, rct.SystemCode as rcolumntypecode, ' .
        'rc.sql as sql, r.sql as rsql, r.xalias as xalias, ' .
        'rc.linkedreportid as linkedreportid, rc.linkedparameter as linkedparameter, ' .
        'rc.linkedcolumnid as linkedcolumnid, rclc.Code as linkedcolumnalias, ' .
        '(select name from iris_Report_Filter where reportid=rc.linkedreportid and code=rc.linkedparameter limit 1) as linkedfiltercaption,' .
        '(select name from iris_Report_Parameter where reportid=rc.linkedreportid and code=rc.linkedparameter limit 1) as linkedparametercaption, ' .
        'rc.width as width ' .
        'from iris_Report r ' .
        'left join iris_Report_Column rc on rc.ReportID=r.ID ' .
        '  left join iris_Report_Column rclc on rclc.ID=rc.linkedcolumnid ' .
        'left join iris_Table_Column tc on rc.ColumnID=tc.ID ' .
        'left join iris_Report_Table rt on rt.ID=rc.Report_TableID ' .
        'left join iris_Table t on rt.TableID=t.ID ' .
        'left join iris_aggregatefunction af on rc.FunctionID=af.ID ' .
        'left join iris_aggregatefunction aft on rc.TotalID=aft.ID ' .
        'left join iris_ColumnType ct on ct.ID=tc.ColumnTypeID ' .
        'left join iris_ColumnType rct on rct.id=rc.ColumnTypeID ' .
        'where r.id=:p_reportid ' .
        'order by rc.Number ';
    $statement = $con->prepare($select_sql);
    $statement->bindParam(':p_reportid', $p_reportid);
    $statement->execute();
    $res = $statement->fetchAll();
    $i = 0;
    $show_info = [];
    foreach ($res as $row) {
        $report_sql = $row['rsql'];

        $select .= 0 == $i ? '' : ', ';

        //Агрегация, если надо
        $func1 = '';
        $func2 = '';
        if ('' != $row['functioncode']) {
            $func1 = $row['functioncode'] . '(';
            $func2 = ')';
        }

        //Тип колонки из карточки колонки отчёта или из типа поля таблицы
        $columntypecode = $row['rcolumntypecode'] ? $row['rcolumntypecode'] : $row['columntypecode'];

        if (!$row['sql']) {
            if ('date' == $columntypecode) {
                //Чтобы по дате не сбивалась сортировка, добавим для сортировки отдельное поле
                $select .= _db_date_to_string($func1 . $row['tablealias'] . '.' . $row['columncode'] . $func2) . ' as ' . $row['columnalias'];
                $select .= ', ' . ($func1 . $row['tablealias'] . '.' . $row['columncode'] . $func2) . ' as ' . $row['columnalias'] . $row['columnalias'] . 'datedate';
            }
            else {
                if ('datetime' == $columntypecode) {
                    //Чтобы по дате не сбивалась сортировка, добавим для сортировки отдельное поле
                    $select .= _db_datetime_to_string($func1 . $row['tablealias'] . '.' . $row['columncode'] . $func2) . ' as ' . $row['columnalias'];
                    $select .= ', ' . ($func1 . $row['tablealias'] . '.' . $row['columncode'] . $func2) . ' as ' . $row['columnalias'] . $row['columnalias'] . 'datetimedatetime';
                }
                else {
                    $select .= $func1 . $row['tablealias'] . '.' . $row['columncode'] . $func2 . ' as ' . $row['columnalias'];
                }
            }
        }
        else {
            if ('date' == $columntypecode) {
                //Чтобы по дате не сбивалась сортировка, добавим для сортировки отдельное поле
                $select .= _db_date_to_string('(' . $row['sql'] . ')') . ' as ' . $row['columnalias'];
                $select .= ', (' . $row['sql'] . ') as ' . $row['columnalias'] . $row['columnalias'] . 'datedate';
            }
            else {
                if ('datetime' == $columntypecode) {
                    //Чтобы по дате не сбивалась сортировка, добавим для сортировки отдельное поле
                    $select .= _db_datetime_to_string('(' . $row['sql'] . ')') . ' as ' . $row['columnalias'];
                    $select .= ', (' . $row['sql'] . ') as ' . $row['columnalias'] . $row['columnalias'] . 'datetimedatetime';
                }
                else {
                    $select .= '(' . $row['sql'] . ') as ' . $row['columnalias'];
                }
            }
        }

        //Заодно формируем и массив метаданных - параметров отображения
        $show_info[ $i ] = [
            'ShowInReport'           => $row['showinreport'],
            'ShowInGraph'            => $row['showingraph'],
            'AxisX'                  => ((($row['xcolumnid'] == $row['columnid']) && ($row['columnid'] != null)) || (($row['columnalias'] != null) && ($row['xalias'] == $row['columnalias']))) ? 1 : 0,
            'Caption'                => $row['showname'],
            'Alias'                  => $row['columnalias'],
            'Type'                   => $columntypecode,
            'Total'                  => $row['totalcode'],
            'LinkedReportID'         => $row['linkedreportid'],
            'LinkedParameter'        => $row['linkedparameter'],
            'LinkedColumnAlias'      => $row['linkedcolumnalias'],
            'LinkedParameterCaption' => $row['linkedparametercaption'] . $row['linkedfiltercaption'],
            'Width'                  => $row['width'],
            'GroupNumber'            => $row['groupnumber'],
            'GroupType'              => $row['grouptype'],
        ];

        $i++;
    }
    $select .= ' ';

    $rowcount = count($res);

    //Сортировка для правильной группировки
    $last_sorded_index = -1; //Сортировку обычную начнём после этого поля
    for ($i = 0; $i < $rowcount; $i++) { //идём до конца, чтобы вычислить $last_sorded_index
        for ($j = $i + 1; $j < $rowcount; $j++) {
            if (($res[ $j ]['groupnumber'] != '' && $res[ $i ]['groupnumber'] > $res[ $j ]['groupnumber']
                    && $res[ $i ]['grouptype'] == 2 && $res[ $j ]['grouptype'] == 2)
                || (('' == $res[ $i ]['groupnumber'] || $res[ $i ]['grouptype'] != 2)
                    && ('' != $res[ $j ]['groupnumber'] && $res[ $j ]['grouptype'] == 2))
            ) {
                $tmp = $res[ $i ];
                $res[ $i ] = $res[ $j ];
                $res[ $j ] = $tmp;
            }
            if ($res[ $i ]['grouptype'] == 2 && $res[ $i ]['ordernumber'] == '') {
                $res[ $i ]['ordernumber'] = -1;
            }
        }
        $last_sorded_index = $res[ $i ]['groupnumber'] && $res[ $i ]['grouptype'] == 2 ? $i : $last_sorded_index;
        if ($last_sorded_index != $i) {
            break;
        }
    }
    //Сортировка (секция ORDER BY)
    for ($i = $last_sorded_index + 1; $i < $rowcount - 1; $i++) {
        for ($j = $i + 1; $j < $rowcount; $j++) {
            if (($res[ $j ]['ordernumber'] != '' && $res[ $i ]['ordernumber'] > $res[ $j ]['ordernumber'])
                || ($res[ $j ]['ordernumber'] != '' && $res[ $i ]['ordernumber'] == '')
            ) {
                $tmp = $res[ $i ];
                $res[ $i ] = $res[ $j ];
                $res[ $j ] = $tmp;
            }
        }
    }
    $i = 0;
    while (($i < $rowcount) && ($res[ $i ]['ordernumber'] != '')) {
        $order .= $order ? ', ' : '';

        //Направление сортировки
        $dir = '';
        if (1 == $res[ $i ]['orderdirection']) {
            $dir = 'asc';
        }
        if (2 == $res[ $i ]['orderdirection']) {
            $dir = 'desc';
        }

        if (('date' == $res[ $i ]['rcolumntypecode'])) {
            $order .= $res[ $i ]['columnalias'] . $res[ $i ]['columnalias'] . 'datedate' . ' ' . $dir;
        }
        else {
            if (('datetime' == $res[ $i ]['rcolumntypecode'])) {
                $order .= $res[ $i ]['columnalias'] . $res[ $i ]['columnalias'] . 'datetimedatetime' . ' ' . $dir;
            }
            else {
                $order .= $res[ $i ]['columnalias'] . ' ' . $dir;
            }
        }
        $i++;
    }
    $order = '' != $order ? 'order by ' . $order : $order;
    $order .= ' ';

    //Группировка (секция GROUP BY)
    for ($i = 0; $i < $rowcount - 1; $i++) {
        for ($j = $i + 1; $j < $rowcount; $j++) {
            if (($res[ $i ]['groupnumber'] < $res[ $j ]['groupnumber'])
                || (('' == $res[ $i ]['groupnumber']) && ('' != $res[ $j ]['groupnumber']))
            ) {
                $tmp = $res[ $i ];
                $res[ $i ] = $res[ $j ];
                $res[ $j ] = $tmp;
            }
        }
    }
    $i = 0;
    while (($i < $rowcount) && ($res[ $i ]['groupnumber'] != '')) {
        if ($res[ $i ]['grouptype'] != 2) {
            $group = '' == $group ? $group : ', ' . $group;
            //$group = $res[$i]['columnalias'].$group;
            //$group = $res[$i]['tablealias'].'.'.$res[$i]['columncode'].$group;
            if ($res[ $i ]['tablealias'] != '' and $res[ $i ]['columncode'] != '') {
                $group = $res[ $i ]['tablealias'] . '.' . $res[ $i ]['columncode'] . $group;
            }
            else {
                $group = $res[ $i ]['columnalias'] . $group;
            }
        }
        $i++;
    }
    $group = '' != $group ? 'group by ' . $group : $group;
    $group .= ' ';


    //Получим список таблиц (секция FROM)
    $select_sql = 'select rt.id as id, rt.Code as tablealias, t.Code as tablecode, ' .
        'rt.ParentTableID as parentid, tc.Code as columncode, ptc.Code as parentcolumncode, ' .
        'prt.Code as parenttablealias, rt.sql as sql ' .
        'from iris_Report r ' .
        'left join iris_Report_Table rt on rt.ReportID=r.ID ' .
        'left join iris_Table t on rt.TableID=t.ID ' .
        'left join iris_Table_Column tc on rt.ColumnID=tc.ID ' .
        'left join iris_Table_Column ptc on rt.ParentColumnID=ptc.ID ' .
        'left join iris_Report_Table prt on rt.ParentTableID=prt.ID ' .
        'where r.id=:p_reportid ' .
        'order by rt.orderpos';
    $statement = $con->prepare($select_sql);
    $statement->bindParam(':p_reportid', $p_reportid);
    $statement->execute();
    $res = $statement->fetchAll();

    $from .= BuildReportSQLFrom($res, '', null);
    $from .= ' ';

    //Получим список условий (секция WHERE)
    $filters = null;
    $select_sql = '(select rf.ID as id, rt.Code as tablealias, tc.Code as columncode, ct.SystemCode as columntypecode, ' .
        'rf.StringValue as stringvalue, rf.IntValue as intvalue, rf.FloatValue as floatvalue, rf.DateValue as datevalue, rf.GUIDValue as guidvalue, ' .
        'rf.Condition as condition, rf.isvisible as isvisible, ' .
        'rf.parentfilterid as parentfilterid, rf.logiccondition as logiccondition, ' .
        "rf.sql as sql, rf.code as paramname, null as realparamname, rf.equation as equation " .
        'from iris_Report_Filter rf ' .
        'left join iris_Report r on rf.ReportID=r.ID ' .
        'left join iris_Report_Table rt on rt.ID=rf.Report_TableID ' .
        'left join iris_Table_Column tc on tc.ID=rf.ColumnID ' .
        'left join iris_ColumnType ct on ct.ID=tc.ColumnTypeID ' .
        'where r.id=:p_reportid) ' .

        'union ' .
        "(select rp.ID as id, t.Code as tablealias, '' as columncode, ct.SystemCode as columntypecode, " .
        'rp.StringValue as stringvalue, rp.IntValue as intvalue, rp.FloatValue as floatvalue, rp.DateValue as datevalue, rp.GUIDValue as guidvalue, ' .
        "'3' as condition, rp.isvisible as isvisible, " .
        "null as parentfilterid, '0' as logiccondition, " .
        "'' as sql, rp.code as paramname, rp.code as realparamname, rp.equation as equation " .
        'from iris_Report_Parameter rp ' .
        'left join iris_Table t on t.ID=rp.TableID ' .
        'left join iris_ColumnType ct on ct.ID=rp.TypeID ' .
        'where rp.reportid=:p_reportid) ' .

        'order by parentfilterid ';
//		"and rf.isvisible='0'";
//echo $select_sql;
    $statement = $con->prepare($select_sql);
    $statement->bindParam(':p_reportid', $p_reportid);
    $statement->execute();
    $res = $statement->fetchAll();
    $param_number = 0; //для нумерации параметров
    //print_r($res);
//echo '<pre>'; print_r($res); echo '</pre>';
    $d_compare_condition = IrisDomain::getDomain('d_compare_condition');
    $d_logic_condition = IrisDomain::getDomain('d_logic_condition');
    foreach ($res as $row) {
        //Значение в зависимости от типа
        $param_value = null;
        $param_name = ($row['paramname'] != '') ? $row['paramname'] : ':p_param_' . $param_number;


        //Если значение фильтра надо искать в передынных параметрах
        //else {
        //Найдем фильтр в переданных параметрах
        if ($p_filters) {
            foreach ($p_filters as $filter) {
                if (($row['id'] == $filter->FilterID) || ($row['id'] == $filter->ParameterID)
                    || (!IsEmptyValue($row['paramname']) && $row['paramname'] == $filter->ParameterName)
                ) {
//echo '<pre>'; print_r($filter); echo '</pre>';
                    $condition = $d_compare_condition->get($filter->Condition); //условие сравнения
                    $param_value = json_decode_str($filter->Value);
                    //Если заполнено условие и значение, то добавим условие
                    if ($param_value != '' && $condition != '') {
//						$param_name = ':p_param_'.$param_number; //название параметра
//						$where .= '' == $where ? '' : ' and ';
//						$where .= $row['tablealias'].'.'.$row['columncode'].' '.$condition.' '.$param_name;
                        $filters[ $param_name ] = $param_value;
                        $param_number++;
                    }
                    else {
                        if (($row['id'] == $filter->ParameterID)
                            || ($row['realparamname'] == $filter->ParameterName)
                        ) {
                            $filters[ $param_name ] = $param_value;
                            $param_number++;
                        }
                    }
                    if ($row['id'] == $filter->ParameterID) {
                        break;
                    }

                    //для дерева or/and
                    $filters_array[ $row['id'] ]['name'] = $param_name;
                    $filters_array[ $row['id'] ]['condition'] = $d_compare_condition->get($filter->Condition);
                    $filters_array[ $row['id'] ]['value'] = $param_value;
                    $filters_array[ $row['id'] ]['parentfilterid'] = $row['parentfilterid'];
                    $filters_array[ $row['id'] ]['logiccondition'] = $d_logic_condition->get($row['logiccondition'], 'db_value', 'sql');
                    $filters_array[ $row['id'] ]['field'] = $row['tablealias'] . '.' . $row['columncode'];
                    $filters_array[ $row['id'] ]['sql'] = $row['sql'];

                    break;
                }
            }
        }
        //}

        //Если значение фильтра надо брать из таблицы
        if ((!$param_value) && ((!$row['isvisible']) || ($row['sql']) || ($row['equation']))) {
            //Если формула
            if ($row['equation']) {
                $param_value = FillFormFromText($row['equation'], 'Report', $p_reportid);
            }
            else {
                if ($row['sql'] && strpos($row['sql'], $param_name) !== false) {
                    // если фильтр содержит sql условие и оно содержит название (код)
                    // параметра, то в запрос нужно передать даже пустой параметр, чтобы
                    // запрос выполнился без ошибок (sql условие добавляется в секцию where всегда)
                    $filters[ $param_name ] = null;
                }
                else {
                    switch ($row['columntypecode']) {
                        case 'string':
                        case 'char':
                        case 'text':
                            $param_value = $row['stringvalue'];
                            break;
                        case 'date':
                        case 'datetime':
                            $param_value = $row['datevalue'];
                            break;
                        case 'int':
                            $param_value = $row['intvalue'];
                            break;
                        case 'float':
                            $param_value = $row['floatvalue'];
                            break;
                        case 'guid':
                            $param_value = $row['guidvalue'];
                            break;
                    }
                }
            }

            //Добавим фильтр только если значение не пустое
            if ($param_value) {
//				$where .= '' == $where ? '' : ' and ';
                $condition = $d_compare_condition->get($row['condition']); //условие сравнения
                //Если условие определено, то дополним фильтры
                if ($condition) {
//					$param_name = $param_name; //название параметра
//					$where .= $row['tablealias'].'.'.$row['columncode'].' '.$condition.' '.$param_name;
                    $filters[ $param_name ] = $param_value;
//echo $param_name .'---'. $param_value;
                    $param_number++;
                }
            }

            //для дерева or/and
            $filters_array[ $row['id'] ]['name'] = $param_name;
            $filters_array[ $row['id'] ]['condition'] = $d_compare_condition->get($row['condition']);
            $filters_array[ $row['id'] ]['value'] = $param_value;
            $filters_array[ $row['id'] ]['parentfilterid'] = $row['parentfilterid'];
            $filters_array[ $row['id'] ]['logiccondition'] = $d_logic_condition->get($row['logiccondition'], 'db_value', 'sql');
            $filters_array[ $row['id'] ]['field'] = $row['tablealias'] . '.' . $row['columncode'];
            $filters_array[ $row['id'] ]['sql'] = $row['sql'];
        }

    }

    //print_r($filters_array);
    $where = Report_GetWhereConditions($filters_array, '');
//echo $where.'-----';
    //print_r($filters_array);
    //echo $where;

    $where = '' != $where ? 'where ' . $where : $where;
    $where .= ' ';

    //Соединяем все в одну строку
    $sql = $select . $from . $where . $group . $order . $limit;
//	$sql = $select.'<br/>'.$from.'<br/>'.$where.'<br/>'.$group.'<br/>'.$order;
//echo $sql;
//print_r($show_info);
    if ($report_sql) {
        $sql = $report_sql;
    }

    $sql = PerformMacroSubstitution($sql); // miv 20.08.2010: добавлена возможность в отчетах использовать функции макроподстановок
    return [$sql, $filters, $show_info];
}


//Сформировать условия с учетом иерархической связки по И/ИЛИ
function Report_GetWhereConditions($p_filters_array, $p_parent_id)
{
    $where = '';
    $where1 = '';
    $logiccondition = !empty($p_filters_array[ $p_parent_id ]['logiccondition']) ? $p_filters_array[ $p_parent_id ]['logiccondition'] : null;
    $logiccondition = $logiccondition ? $logiccondition : 'and';
    $CONDITION_JSONB_EXISTS = 'одно из';

    if ($p_filters_array != null) {
        foreach ($p_filters_array as $key => $filter) {
            if ($p_parent_id == $filter['parentfilterid']) {
                if (!empty($filter['sql'])) {
                    $where .= '' == $where ? '' : ' ' . $logiccondition . ' ';
                    $where .= '(' . $filter['sql'] . ')';
                }
                else {
                    if ($filter['field'] != '.' && $filter['condition'] != '' && $filter['value'] != '') {
                        $where .= '' == $where ? '' : ' ' . $logiccondition . ' ';
                        if ($filter['condition'] != $CONDITION_JSONB_EXISTS) {
                            $where .= $filter['field'] . ' ' . $filter['condition'] . ' ' . $filter['name'];
                        }
                        else {
                            // json: postgresql 8.3
                            // каждый текстовый элемент массива возвращаяется с кавычками,
                            // поэтому он дополняется символами [ и ], переводится в json массив из которого берется первый элемент
                            // это лучше, чем trim кавычек, так как при этом еще анэскейпятся значения вида "\u2603"
                            // http://dba.stackexchange.com/questions/54283/how-to-turn-json-array-into-postgres-array/57121#57121
                            $where .= $filter['field'] . ' in ' . '(select (\'[\'||elem::text||\']\')::json->>0 from json_array_elements(' . $filter['name'] . ') elem)';
                            // jsonb: postgresqls 8.4+
                            // $where .= 'jsonb_exists('.$filter['name'].'::jsonb, '.$filter['field'].')';
                        }
                    }
                    else {
                        $where1 = Report_GetWhereConditions($p_filters_array, $key);
                        $where = ('' != $where1) && ('' != $where) ? $where . ' ' . $logiccondition . ' ' . $where1 : $where . $where1;
                    }
                }
            }
        }
    }
//echo $where;
    $where = $where ? '(' . $where . ')' : '';

    return $where;
}


//Построение секции FROM запроса (дерево, рекурсия)
function BuildReportSQLFrom($data, $parentid, $ban)
{
    $from = '';

    //Для избежания зацикливания
    if ($ban != null) {
        foreach ($ban as $banid) {
            if ($banid == $parentid) {
                return $from;
            }
        }
    }

    //Выбираем все дочерние left join либо родителей (если parentid='')
    foreach ($data as $row) {
        if ($row['parentid'] == $parentid) {
            //Если родитель (корень дерева)
            if ('' == $parentid) {
                if ($from) {
                    $from .= ', ';
                }
                $from .= $row['sql'] ? $row['sql'] : $row['tablecode'] . ' ' . $row['tablealias'];
            }
            //Если дочерний
            else {
                $from .= $row['sql']
                    ? ' ' . $row['sql']
                    : ' left join ' . $row['tablecode'] . ' ' . $row['tablealias'] .
                    ' on ' . $row['tablealias'] . '.' . $row['columncode'] . '=' .
                    $row['parenttablealias'] . '.' . $row['parentcolumncode'];
            }

            //Добавим текущего родителя в бан
            $ban[] = $parentid;

            //Просканируем на наличие дочерних
            $from .= BuildReportSQLFrom($data, $row['id'], $ban);
        }
    }

    return $from;
}


//Выполнение SQL и получение данных в массив
function BuildReportData($p_show_info, $p_sql, $p_params, $p_con = null)
{
    //Получим данные отчета
    $con = GetConnection($p_con);

    //Пустые значения приведем к null
    if ($p_params != null) {
        foreach ($p_params as $key => $value) {
            if ('' == $value) {
                $p_params[ $key ] = null;
            }
        }
    }

    $statement = $con->prepare($p_sql, [PDO::ATTR_EMULATE_PREPARES => true]);
    $statement->execute($p_params);
    if ($statement->errorCode() != '00000') {
        return [false, $statement->errorInfo()];
    }

    $res_data = $statement->fetchAll(PDO::FETCH_ASSOC);
    //return $res_data;

    // miv 10.01.2012: проставление нумерации в колонке с алиасом rownum
    if (!empty($res_data[0]['rownum'])) {
        $rownum = 1;
        foreach ($res_data as $key => $value) {
            $res_data[ $key ]['rownum'] = $rownum++;
        }
    }

    return [$res_data, false];
}

/*
//Подготавливает список установленных фильтров
function BuildReportFilters($p_filters) {
  $result = '';
  //TODO: проверять, есть ли такие фильтры в данном отчёте (теперь проверяется и строится сипсок фильтров в Report_GetFilters)

	foreach ($p_filters as $filter) {
		if (json_decode_str($filter->Value) != '') {
			$result .=
				'<tr>'.
				'<td style="white-space: nowrap;">'.json_decode_str($filter->Name).':</td>'.
				'<td style="white-space: nowrap;">'.json_decode_str($filter->Caption).'</td>'.
				'</tr>';
		}
	}

	if ($result) {
		$result =
			//'<h2>Фильтры</h2>'.
			'<table><tbody>'.
			$result.
			'</tbody></table>';
	}
	return $result;
}
*/

//Построение таблицы
function BuildReportTable($p_data, $p_show_info, $p_sql, $p_params, $p_errorinfo = false, $p_format = null, $class_column = null)
{
    global $dec_point;
    global $thousands_sep;
    $result = '';
    $csv_column_delimeter = GetSystemVariableValue('CSV_delimeter');

    if (empty($csv_column_delimeter)) {
        $csv_column_delimeter = ';';
    }

    //Если формат csv
    if ($p_format == 'csv') {
        // Файлоподобная обертка для последующего использования fputcsv()
        $fp = fopen('php://temp', 'r+');

        $row_data = [];
        foreach ($p_show_info as $meta) {
            if (1 == $meta['ShowInReport']) {
                $row_data[] = $meta['Caption'];
            }
        }
        fputcsv($fp, $row_data, $csv_column_delimeter);

        foreach ($p_data as $index => $row) {
            if ($index === 'javascript_bottom') {
                continue;
            }

            $i = 0;
            foreach ($p_show_info as $meta) {
                if (1 == $meta['ShowInReport']) {
                    $row_data[ $i ] = $row[ $meta['Alias'] ];
                    $i++;
                }
            }
            fputcsv($fp, $row_data, $csv_column_delimeter);
        }
        rewind($fp);
        $result = stream_get_contents($fp);
        fclose($fp);

        // Если необходимо, преобразуем результат в указанную кодировку
        $encoding = GetSystemVariableValue('CSV_Encoding');
        if ($encoding) {
            $result = iconv(GetDefaultEncoding(), $encoding, $result);
        }

        return $result;
    }

//  echo '<pre>';
//  print_r($p_show_info);
//  echo '</pre>';

    //Рисуем заголовки
    $result_header = '<tr>';
    $table_field_count = 0;
    foreach ($p_show_info as $meta) {
        if (1 == $meta['ShowInReport'] && $meta['GroupType'] != 2) {
            $result_header .= '<th class="grid"' . ($meta['Width'] ? ' width="' . $meta['Width'] . '"' : '') . '>' . htmlspecialchars($meta['Caption']) . '</th>';
            $table_field_count++;
        }
    }
    $result_header .= '</tr>';

    //Рисуем строки
    $class = 'odd';
    $total = [];
    $havetotal = false;

    // Подытог, используется для группировки в отчете без group by
    $havegroups = false;
    $prev = [];
    $subtotal = [];
    $subtotal_count = 0;

    $rownum = 0;
    foreach ($p_data as $index => $row) {
        if ($index === 'javascript_bottom') {
            continue;
        }

        if ($class_column) {
            $class = $row[ $class_column ];
        }
        $result_row = '';
        $i = 0;
        $header_number = 2;

        $colcount = 0;

        $subtotal_count++;

        foreach ($p_show_info as $meta) {
            if (1 == $meta['ShowInReport']) {


                // TODO: refactor subtotal
                if ($rownum && $colcount == 0 && $prev[ $meta['Alias'] ] != $row[ $meta['Alias'] ]) {
                    if ($havegroups && $havetotal) {
                        $result .= '<tr class="total">';
                        foreach ($subtotal as $key => $value) {
                            $typeclass = $p_show_info[ $key ]['Type'];
                            if ('AVG' == $p_show_info[ $key ]['Total']) {
                                $value = $subtotal_count <= 0 ? '' : $value = $value / $subtotal_count;
                                $typeclass = 'float';
                            }
                            if (('float' == $typeclass) && ($value != '')) {
                                $value = number_format($value, 2, $dec_point, $thousands_sep);
                            }
                            if ($p_show_info[ $key ]['GroupType'] != 2) {
                                $result .= '<td class="total ' . $typeclass . '">' . $value . '</td>';
                            }
                        }
                        $result .= '</tr>';
                    }
                }


                if ($meta['GroupType'] == 2 && (!$rownum || $prev[ $meta['Alias'] ] != $row[ $meta['Alias'] ])) {
                    $result .= '<tr><td colspan="' . $table_field_count . '"><h' . $header_number . '>'
                        . $row[ $meta['Alias'] ] . '</h' . $header_number . '></td></tr>';
                    $header_number++;
                    $havegroups = true;
                    $class = 'odd';
                    $subtotal = [];
                    $subtotal_count = 0;
                }
                $prev[ $meta['Alias'] ] = $row[ $meta['Alias'] ];

                //Приведем к нужному формату
                $value = htmlspecialchars($row[ $meta['Alias'] ]);
                if (('float' == $meta['Type']) && ($value != '')) {
                    $value = number_format($value, 2, $dec_point, $thousands_sep);
                }
                //TODO: str_replace при возможности заменить на экранировку \"
                $showvalue = $meta['LinkedReportID'] && $meta['LinkedParameter']
                    ? "<a href=\"#\" onclick=\"openReport('" . $meta['LinkedReportID'] . "', '" . $meta['LinkedParameter'] . "', '" .
                    ($meta['LinkedColumnAlias'] ? $row[ $meta['LinkedColumnAlias'] ] : $value) .
                    "', '" . $meta['LinkedParameterCaption'] . "', '" . iris_str_replace('"', '', $value) . "');\">$value</a>"
                    : $value;
                if ($meta['GroupType'] != 2) {
                    $result_row .= '<td class="grid ' . $meta['Type'] . '">' . $showvalue . '</td>';
                }

                //Если надо считать итог, то посчитаем его
                if (empty($total[ $i ])) {
                    $total[ $i ] = '';
                }
                $total[ $i ] .= '';
                $subtotal[ $i ] = isset($subtotal[$i]) ? $subtotal[$i] : '';
                switch ($meta['Total']) {
                    case 'SUM':
                    case 'AVG':
                        $total[ $i ] += $row[ $meta['Alias'] ];
                        $subtotal[ $i ] += $row[ $meta['Alias'] ];
                        $havetotal = true;
                        break;

                    case 'COUNT':
                        $total[ $i ] += 1;
                        $subtotal[ $i ] += 1;
                        $havetotal = true;
                        break;

                    case 'MAX':
                        if (0 == count($total[ $i ])) {
                            $total[ $i ] = $row[ $meta['Alias'] ];
                            $subtotal[ $i ] = $row[ $meta['Alias'] ];
                        }
                        else {
                            $total[ $i ] = $total[ $i ] > $row[ $meta['Alias'] ] ? $total[ $i ] : $row[ $meta['Alias'] ];
                            $subtotal[ $i ] = $subtotal[ $i ] > $row[ $meta['Alias'] ] ? $subtotal[ $i ] : $row[ $meta['Alias'] ];
                        }
                        $havetotal = true;
                        break;

                    case 'MIN':
                        if (0 == count($total[ $i ])) {
                            $total[ $i ] = $row[ $meta['Alias'] ];
                            $subtotal[ $i ] = $row[ $meta['Alias'] ];
                        }
                        else {
                            $total[ $i ] = $total[ $i ] < $row[ $meta['Alias'] ] ? $total[ $i ] : $row[ $meta['Alias'] ];
                            $subtotal[ $i ] = $subtotal[ $i ] < $row[ $meta['Alias'] ] ? $subtotal[ $i ] : $row[ $meta['Alias'] ];
                        }
                        $havetotal = true;
                        break;
                }
                $total[ $i ] = htmlspecialchars($total[ $i ]);
                $subtotal[ $i ] = htmlspecialchars($subtotal[ $i ]);
                $colcount++;
            }
            $i++;
        }
        if ($header_number > 2) {
            $result .= $result_header;
        }
        $result_row = '<tr class="grid ' . $class . '" onclick="selectreportrow(this);">'
            . $result_row . '</tr>';
        $result .= $result_row;
        if ($class_column) {
            $class = $row[ $class_column ];
        }
        else {
            $class = 'even' == $class ? 'odd' : 'even';
        }
        $rownum++;
    }
    $result = (!$havegroups ? $result_header : '') . $result;

    // 28.11.2011: если в отчете ошибка, то выведем соответсвующее сообщение, а администратору sql отчета и текст ошибки
    if (($p_errorinfo != false) and ($p_errorinfo[0] != '00000')) {
        $admin_info = (IsUserInAdminGroup() == true) ? '<span class="errbtn" onclick="$(\'errorinfo\').toggle()">?</span><div id="errorinfo" style="display: none">' . $p_errorinfo[2] . '<br>' . $p_sql . '<br>' . var_export($p_params, true) . '</div>' : '';
        $result .= '<tr class="grid ' . $class . '"><td colspan="' . count($p_show_info) . '">Невозможно построить отчет: ошибка в запросе' . $admin_info . '</td></tr>';
    }

    //Если есть итог, то нарисуем его
    if ($havetotal) {


        // TODO: refactor subtotal
        if ($havegroups && $havegroups) {
            $result .= '<tr class="total">';
            foreach ($subtotal as $key => $value) {
                $typeclass = $p_show_info[ $key ]['Type'];
                if ('AVG' == $p_show_info[ $key ]['Total']) {
                    $value = count($p_data) <= 0 ? '' : $value = $value / count($p_data);
                    $typeclass = 'float';
                }
                if (('float' == $typeclass) && ($value != '')) {
                    $value = number_format($value, 2, $dec_point, $thousands_sep);
                }
                if ($p_show_info[ $key ]['GroupType'] != 2) {
                    $result .= '<td class="total ' . $typeclass . '">' . $value . '</td>';
                }
            }
            $result .= '</tr>';
            $result .= '<tr><td colspan="' . $table_field_count . '"><h2>'
                . 'Итого' . '</h2></td></tr>';
        }


        // TODO: refactor total
        $result .= '<tr class="total">';
        foreach ($total as $key => $value) {
            $typeclass = $p_show_info[ $key ]['Type'];
            if ('AVG' == $p_show_info[ $key ]['Total']) {
                $value = count($p_data) <= 0 ? '' : $value = $value / count($p_data);
                $typeclass = 'float';
            }
            if (('float' == $typeclass) && ($value != '')) {
                $value = number_format($value, 2, $dec_point, $thousands_sep);
            }
            if ($p_show_info[ $key ]['GroupType'] != 2) {
                $result .= '<td class="total ' . $typeclass . '">' . $value . '</td>';
            }
        }
        $result .= '</tr>';
        $result = '<table class="report"><tbody>' . $result;
    }
    else {
        $result = '<table class="report"><tbody>' . $result;
    }


    $result .= '</tbody></table>';

    return $result;
}

// Построение таблицы отчета в виде карточек
function BuildReportCards($p_data, $p_show_info, $p_sql, $p_params, $p_errorinfo = false, $p_layout = null)
{
    // если в отчете ошибка, то выведем ее
    if (($p_errorinfo != false) and ($p_errorinfo[0] != '00000')) {
        $admin_info = (IsUserInAdminGroup() == true) ? '<span class="errbtn" onclick="$(\'errorinfo\').toggle()">?</span><div id="errorinfo" style="display: none">' . $p_errorinfo[2] . '<br>' . $p_sql . '<br>' . var_export($p_params, true) . '</div>' : '';
        $result .= 'Невозможно построить отчет: ошибка в запросе' . $admin_info;

        return $result;
    }

    // создаем массив, элементы которого - маленькие таблички, содержащие данные по 1 строке отчета
    $tables_array = [];
    foreach ($p_data as $row) {
        $table_html = '<table class="report">';
        foreach ($p_show_info as $meta) {
            if (1 == $meta['ShowInReport']) {
                $table_html .= '<tr>';
                $table_html .= '<th class="grid" style="width: 25%">' . $meta['Caption'] . '</th>';
                $table_html .= '<td class="grid" style="border: 1px solid #AAE;">' . $row[ $meta['Alias'] ] . '</td>';
                $table_html .= '</tr>';
            }
        }
        $table_html .= '</table>';
        $tables_array[] = $table_html;
    }

    // если layout не указан, то заполним стандартными значениями
    if ($p_layout == null) {
        $p_layout['row_count'] = 5;        // количество колонок
        $p_layout['column_count'] = 2;    // количество строк
    }
    $rowcount = 0; // число "строк" отчета

    $break_style = 'style="page-break-before: always;"';
    $result = '';
    $result .= '<table ' . $break_style . '>';
    $tables_array_count = count($tables_array);

    // выведем маленькие таблички в нужной раскладке (column_count * row_count)
    for ($di = 0; $di <= $tables_array_count; $di += $p_layout['column_count']) {
        $rowcount++;
        $result .= '<tr>';
        for ($i = 0; $i < $p_layout['column_count']; $i++) {
            $result .= '<td>' . $tables_array[ $di + $i ] . '</td>';
            $index++;
        }
        $result .= '</tr>';

        // если нужно разбить страницы и это не последняя страница, то разобьем
        if (($rowcount % $p_layout['row_count'] == 0) and ($di + $p_layout['column_count'] < $tables_array_count)) {
            $result .= '</table><table ' . $break_style . '>';
        }
    }
    $result .= '</table>';

    return $result;
}

// Построение таблицы отчета в транспонированном виде
function BuildReportTransposedTable($p_data, $p_show_info, $p_sql, $p_params, $p_errorinfo = false)
{
    global $dec_point;
    global $thousands_sep;

    $data_length = count($p_data);
    $transp_data = []; // транспонированный массив, сразу с колонками и значениями
    $j = 0;
    foreach ($p_show_info as $meta) {
        if ($meta['ShowInReport'] == 1) {
            $transp_data[ $j ][] = $meta['Caption'];
            for ($i = 0; $i < $data_length; $i++) {
                $transp_data[ $j ][] = $p_data[ $i ][ $meta['Alias'] ];
            }
        }
        $j++;
    }
    //header("content-type: text/plain");

    $result = '<table class="report grid-trans"><tbody>';
    foreach ($transp_data as $rownum => $row) {
        $result .= '<tr class="grid">';
        foreach ($row as $index => $column) {
            if ($index == 0) {
                $result .= '<th class="grid" style="width: 25%">' . $column . '</th>';
                continue;
            }

            //Приведем к нужному формату
            $value = $column;
            if (('float' == $p_show_info[ $rownum ]['Type']) && ($value != '')) {
                $value = number_format($value, 2, $dec_point, $thousands_sep);
            }
            $result .= '<td class="grid ' . ($index % 2 == 0 ? 'even' : 'odd') . ' ' . $p_show_info[ $rownum ]['Type'] . '">' . $value . '</td>';
        }
        $result .= '</tr>';
    }

    $result .= '</tbody></table>';

    return $result;
}

//Функция для сортировки групп
function report_compare_groups($a, $b)
{
    return $a['GroupNumber'] < $b['GroupNumber'] ? -1 : ($a['GroupNumber'] == $b['GroupNumber'] ? 0 : 1);
}

//Построение графика
function BuildReportGraph($p_data, $p_show_info, $p_reportid)
{
    //Тип графика
    list ($p_graphtype, $showzero, $showaxis, $showlabels, $graph_width, $graph_height, $colorschemeid) =
        GetFieldValuesByID('Report', $p_reportid, [
            'GraphType', 'ShowZero', 'ShowAxis', 'ShowLabels', 'GraphWidth', 'GraphHeight', 'ColorSchemeID',
        ]);
    $graph_width = $graph_width ? $graph_width : 150;
    $graph_height = $graph_height ? $graph_height : 150;

    $colorscheme = null;
    if ($colorschemeid) {
        list ($colorscheme, $lightscheme) = GetFieldValuesByID('ColorScheme', $colorschemeid, ['Scheme', 'Highlight']);
    }
    if (!$colorscheme) {
        $colorscheme = '#6F1B75, #0F408D, #DA2228, #CA147A, #FCF302, #E8801B, #15993C, #8DC922, #0092CE, #87CCEE';
        $lightscheme = $colorscheme;
    }
    if (!$lightscheme) {
        $lightscheme = $colorscheme;
    }
    //echo '-<pre>'.$colorscheme.'</pre>-';
    $colorscheme = iris_str_replace(chr(10), '', $colorscheme);
    $colorscheme = iris_str_replace(chr(13), '', $colorscheme);
    $colors = explode(',', $colorscheme);
    $lightscheme = iris_str_replace(chr(10), '', $lightscheme);
    $lightscheme = iris_str_replace(chr(13), '', $lightscheme);
    $litecolors = explode(',', $lightscheme);

    $d_graph_type = IrisDomain::getDomain('d_graph_type');
    $gtype = $d_graph_type->get($p_graphtype, 'db_value', 'code');
    $graphtype = $gtype;

    $result = '';
    $result .= '<div id="jxgbox" class="jxgbox" ' .
        'style="width: ' . $graph_width . 'mm; height: ' . $graph_height . 'mm; ' .
        'position: relative; overflow: hidden;">';
    $result .= '</div>';

    $result .= '<script>';

    $data_values = null;
    $data_valuesB = null;
    $caption_values = '';
    $minx = $maxx = $miny = $maxy = 0;
    $xvalues = 0;
    $isfloat = true;

    //Составляем список группировок (для мульти гистограммы)
    $i = 0;
    $groups = [];
    for ($j = 0; $j < count($p_show_info); $j++) {
        if ($p_show_info[ $j ]['GroupNumber'] && $p_show_info[ $j ]['GroupType'] == 2) {
            $groups[ $i ]['GroupNumber'] = $p_show_info[ $j ]['GroupNumber'];
            //$groups[$i]['FieldNumber'] = $j;
            $groups[ $i ]['FieldName'] = $p_show_info[ $j ]['Alias'];
            $i++;
        }
    }
    //Сортируем группировки по-порядку (для мульти гистограммы)
    usort($groups, "report_compare_groups");

    //Составляем массив цветов (по уникальным значениям последней группировки) (для мульти гистограммы)
    $group_colors = null;
    $group_litecolors = null;
    $i = 0;
    if (count($groups) >= 1) {
        foreach ($p_data as $row) {
            $field = $groups[count($groups) - 1]['FieldName'];
            if (isset($row[$field]) && !empty($group_colors[$row[$field]])) {
                $group_colors[$row[$field]] = $colors[$i % count($colors)];
                $group_litecolors[$row[$field]] = $litecolors[$i % count($litecolors)];
                $i++;
            }
        }
    }


    //Рисуем значения для осей x и y
    $group_level = 0;
    $group_shift = 0;
    $prev_row = null;
    for ($rownum = 0; $rownum < count($p_data); $rownum++) {
        $row = isset($p_data[ $rownum ]) ? $p_data[ $rownum ] : null;

        if ($prev_row != null) {
            //Если сменилось значение в любой из группировок (кроме последней), то сбрасываем уровень
            //(для мульти гистограммы)
            for ($i = 0; $i < count($groups) - 1; $i++) {
                if ($row[ $groups[ $i ]['FieldName'] ] != $prev_row[ $groups[ $i ]['FieldName'] ]) {
                    $group_level = 0;
                    //Если сменилось значение в любой из группировок (кроме 2-х последних), то делаем отступ
                    //(для мульти гистограммы)
                    $group_shift += count($groups) - $i - 1;
                    break;
                }
            }
        }

        $i = 0;
        $zero = true; //Если значение нулевое
        $value = null;
        foreach ($p_show_info as $meta) {
            if ($meta['AxisX']) {
                $caption = "'" . htmlspecialchars($row[ $meta['Alias'] ]) . "'";
                $caption1 = htmlspecialchars($row[ $meta['Alias'] ]);
            }
            else {
                if ((1 == $meta['ShowInGraph'])) {
                    $value[ $i ] = htmlspecialchars($row[ $meta['Alias'] ]);
                    $coord[ $i ][ $rownum ]['x'] = $group_shift;
                    $coord[ $i ][ $rownum ]['y'] = $group_level;
                    $group_level += $value[ $i ]; //TODO: Если несколько колонок отображать на графике, то надо вести несколько group_level
                    if ((float)$row[ $meta['Alias'] ]) {
                        $zero = false;
                    }
                    $i++;
                }
            }
        }
        //Если значение отображаем, то включим его в список значений и скорректируем масштаб
        if ((!$zero) || ($showzero)) {
            if ('bar_ml' == $gtype) {
                $xvalues = $group_shift;
            }
            else {
                $xvalues++;
            }
            if ('bar_h' != $gtype) {
                $caption_values .= '' == $caption_values ? '' : ',';
                $caption_values .= $caption;
            }
            else {
                $caption_values = '' == $caption_values ? '' : ',' . $caption_values;
                $caption_values = $caption . $caption_values;
            }
            $captions[] = $caption1; //для point

            if (1 == $xvalues) {
                $minx = (float)$caption1;
                $maxx = (float)$caption1;
            }
            $minx = (float)$caption1 < $minx ? (float)$caption1 : $minx;
            $maxx = (float)$caption1 > $maxx ? (float)$caption1 : $maxx;
            if (!is_float($caption1) || !$isfloat) {
                $isfloat = false;
                $minx = 1;
                $maxx = $xvalues;
            }

            for ($j = 0; $j < count($value); $j++) {
                if ('bar_h' == $gtype) {
                    $data_values[ $j ] = '' == $data_values[ $j ] ? '' : ',' . $data_values[ $j ];
                    $data_values[ $j ] = (float)$value[ $j ] . $data_values[ $j ];
                }
                else {
                    $data_values[ $j ] =  isset($data_values[ $j ]) ? $data_values[ $j ] : null;
                    if ('funnel' == $gtype) {
                        $data_values[ $j ] .= '' == $data_values[ $j ] ? '' : ',';
                        $data_values[ $j ] .= (float)$value[ $j ];
                        $data_valuesB[ $j ] .= '' == $data_valuesB[ $j ] ? '' : ',';
                        $data_valuesB[ $j ] .= -(float)$value[ $j ];
                    }
                    else {
                        $data_values[ $j ] .= '' == $data_values[ $j ] ? '' : ',';
                        $data_values[ $j ] .= (float)$value[ $j ];
                    }
                }
                $data[ $j ][] = (float)$value[ $j ]; //для point

                if ('bar_ml' == $gtype) {
                    //TODO: Если значения отрицательные, то надо вести 2 шкалы group_level
                    $miny = 0;
                    $maxy = max((float)$value[ $j ] + $group_level, $maxy);
                }
                else {
                    if ((1 == $xvalues) && (0 == $j)) {
                        $miny = (float)$value[ $j ];
                        $maxy = (float)$value[ $j ];
                    }
                    $miny = (float)$value[ $j ] < $miny ? (float)$value[ $j ] : $miny;
                    $maxy = (float)$value[ $j ] > $maxy ? (float)$value[ $j ] : $maxy;
                }
            }
        }
        $prev_row = $row;
    }

    if (!$caption_values) {
        return '';
    }

    //Ось X
    $result .=
        'var labelArr = [' . $caption_values . '];';

    //Ось Y. Возможно несколько линий, поэтому в цикле делаем несколько массивов
    for ($i = 0; $i < count($data_values); $i++) {
        $result .= 'var dataArr' . $i . ' = [' . $data_values[ $i ] . '];';
        if ('funnel' == $gtype) {
            $result .= 'var dataArrB' . $i . ' = [' . $data_valuesB[ $i ] . '];';
        }
    }


    //Автомасштабирование
    if (('bar' == $gtype) || ('bar_h' == $gtype) || ('bar_ml' == $gtype)) {
        $miny = $miny >= 0 ? 0 : $miny;
        $minx = $minx >= 0 ? 0.5 : $minx;
        $maxx += 0.5;
    }
    if ('funnel' == $gtype) {
        $miny = -$maxy;
    }
    $koef = 0.15;
    $minx -= abs($maxx - $minx) * $koef;
    $miny -= abs($maxy - $miny) * $koef;
    $maxx += abs($maxx - $minx) * $koef;
    $maxy += abs($maxy - $miny) * $koef;


    $params = '';

    $color_list = '';
    $light_list = '';
    foreach ($colors as $color) {
        $color = trim($color);
        $color_list .= $color_list ? ',' : '';
        $color_list .= "'$color'";
    }
    foreach ($litecolors as $color) {
        $color = trim($color);
        $light_list .= $light_list ? ',' : '';
        $light_list .= "'$color'";
    }

    $mmkoef = 380 / 100; //В 380 пикселях отображается 100 мм

    switch ($gtype) {
        case 'pie':
            $result .=
                'var board = JXG.JSXGraph.initBoard("jxgbox", {' .
                'showNavigation: true, ' .
                'showCopyright: false, ' .
                'originX: ' . ($graph_width * $mmkoef / 2) . ', ' .
                'originY: ' . ($graph_height * $mmkoef / 2) . ', ' .
                //5 - Пирог рисуется на 4-х единицах. Мы на графике показываем 5 единиц, чтобы уместилось
                'unitX: ' . (($graph_width * $mmkoef / 2) / 5) . ', ' .
                'unitY: ' . (($graph_height * $mmkoef / 2) / 5) . ', ' .
                'axis: ' . ($showaxis ? 'true' : 'false') .
                '});' .
                'function barChart() {' .
                'board.suspendUpdate();';
            $params =
                'highlightOnSector: true,' .
                'center: [0.001, 0.001], ' .
                'highlightStrokeColor: "white",' .
                'strokeColor: "white",' .
                "colors: [$color_list]," .
                "highlightColors: [$light_list]," .
                "highlightOnSector: true," .
                "showinfobox: true," .
                "showLabels: " . ($showlabels ? 'true' : 'false') . ",";
//        "highlightBySize: true,";
            break;

        case 'bar':
            $result .=
                'board = JXG.JSXGraph.initBoard("jxgbox", {' .
                'showNavigation: true, ' .
                'showCopyright: false,' .
                'boundingbox: [' . $minx . ', ' . $maxy . ', ' . $maxx . ', ' . $miny . '],' .
                'axis: ' . ($showaxis ? 'true' : 'false') .
                '});' .
                'function barChart() {' .
                'board.suspendUpdate();';
            $params =
//				'highlightStrokeColor: "white",'.
//				'strokeColor: "black",'.
                "colors: [$color_list]," .
                "highlightColors: [$light_list]," .
                "showinfobox: true," .
                "hasInnerPoints: true," . //При наведении мышкой на бар это будет обрабатываться
                "showLabels: " . ($showlabels ? 'true' : 'false') . ",";
            break;

        case 'bar_h':
            $result .=
                'board = JXG.JSXGraph.initBoard("jxgbox", {' .
                'showNavigation: true, ' .
                'showCopyright: false,' .
                'boundingbox: [' . $miny . ', ' . $maxx . ', ' . $maxy . ', ' . $minx . '],' .
                'axis: ' . ($showaxis ? 'true' : 'false') .
                '});' .
                'function barChart() {' .
                'board.suspendUpdate();';
            $graphtype = 'bar';
            $params =
                'dir: "horizontal",' .
                'highlightStrokeColor: "white",' .
                'strokeColor: "black",' .
                "colors: [$color_list]," .
                "highlightColors: [$light_list]," .
                "showinfobox: true," .
                "hasInnerPoints: true," . //При наведении мышкой на бар это будет обрабатываться
                "showLabels: " . ($showlabels ? 'true' : 'false') . ",";
            break;

        case 'line':
        case 'spline':
            $result .=
                'board = JXG.JSXGraph.initBoard("jxgbox", {' .
                'showNavigation: true, ' .
                'showCopyright: false,' .
                'boundingbox: [' . $minx . ', ' . $maxy . ', ' . $maxx . ', ' . $miny . '],' .
                'axis: ' . ($showaxis ? 'true' : 'false') .
                '});' .
                'function barChart() {' .
                'board.suspendUpdate();';
            $params =
                'style: JXG.POINT_STYLE_CIRCLE_BIG,' .
                'showInfobox: false,' .
                "strokeColor: '" . $colors[0] . "'," .
                "highlightStrokeColor: '" . $litecolors[0] . "'," .
                'fillColor: "white",';
            break;

        case 'funnel':
            $graphtype = 'line';
            $result .=
                'board = JXG.JSXGraph.initBoard("jxgbox", {' .
                'showNavigation: true, ' .
                'showCopyright: false,' .
                'boundingbox: [' . $minx . ', ' . $maxy . ', ' . $maxx . ', ' . $miny . '],' .
//					'boundingbox: ['.$miny.', '.$maxx.', '.$maxy.', '.$minx.'],'.
                'axis: ' . ($showaxis ? 'true' : 'false') .
                '});' .
                'function barChart() {' .
                'board.suspendUpdate();';
            $params =
                'style: JXG.POINT_STYLE_CIRCLE_BIG,' .
                'showInfobox: false,' .
                "strokeColor: '" . $colors[0] . "'," .
                "highlightStrokeColor: '" . $litecolors[0] . "'," .
                'fillColor: "white",';
            break;

        case 'funnel_h':
            $graphtype = 'line';
            $result .=
                'board = JXG.JSXGraph.initBoard("jxgbox", {' .
                'showNavigation: true, ' .
                'showCopyright: false,' .
                'boundingbox: [' . $miny . ', ' . $maxx . ', ' . $maxy . ', ' . $minx . '],' .
                'axis: ' . ($showaxis ? 'true' : 'false') .
                '});' .
                'function barChart() {' .
                'board.suspendUpdate();';
            $params =
                'style: JXG.POINT_STYLE_CIRCLE_BIG,' .
                'showInfobox: false,' .
                "strokeColor: '" . $colors[0] . "'," .
                "highlightStrokeColor: '" . $litecolors[0] . "'," .
                'fillColor: "white",';
            break;

        case 'bar_ml':
            $graphtype = 'line';
            $result .=
                'board = JXG.JSXGraph.initBoard("jxgbox", {' .
                'showNavigation: true, ' .
                'showCopyright: false,' .
                'boundingbox: [' . $minx . ', ' . $maxy . ', ' . $maxx . ', ' . $miny . '],' .
                'axis: ' . ($showaxis ? 'true' : 'false') .
                '});' .
                'function barChart() {' .
                'board.suspendUpdate();';
            $params =
                'style: JXG.POINT_STYLE_CIRCLE_BIG,' .
                'showInfobox: false,' .
                "strokeColor: '" . $colors[0] . "'," .
                "highlightStrokeColor: '" . $litecolors[0] . "'," .
                'fillColor: "white",';
            break;
    }

    $maxy2 = $miny;

    $i = 0;
    foreach ($p_show_info as $meta) {
        if (1 == $meta['ShowInGraph']) {

            if ('funnel' != $gtype && 'funnel_h' != $gtype && 'bar_ml' != $gtype) {
                $result .=
                    '  var a' . $i . ' = board.createElement("chart", dataArr' . $i . ', {' .
                    '    chartStyle: "' . $graphtype . '", ' .
                    '    labels: labelArr, ' .
                    '    labelArray: labelArr, ' .
                    $params .
                    '    strokeWidth: 4' .
                    '  });';
            }
            /*
			//Если воронка, то нарисуем еще и низушку
			if (('funnel' == $gtype)) {
 				$result .=
					'  var b'.$i.' = board.createElement("chart", dataArrB'.$i.', {'.
					'    chartStyle: "'.$graphtype.'", '.
					'    labels: labelArr, '.
					'    labelArray: labelArr, '.
					$params.
					'    strokeWidth: 4'.
					'  });';
			}
*/

            //Если график линейный, то нарисуем еще и точки с caption
            if (('line' == $gtype) || ('spline' == $gtype)
                || ('funnel' == $gtype) || ('funnel_h' == $gtype)
            ) {
                //for ($j=0; $j<count($captions); $j++) {
                $j = $i;
                //echo count($data[$j]);
                for ($k = 0; $k < count($data[ $j ]); $k++) {
                    $x = $isfloat ? $captions[ $j ] : $k + 1;

                    $max = $j;
                    $min = $j;
                    for ($m = 0; $m < count($data); $m++) {
                        if ($data[ $m ][ $k ] > $data[ $j ][ $k ]) {
                            $max = $m;
                        }
                        if ($data[ $m ][ $k ] < $data[ $j ][ $k ]) {
                            $min = $m;
                        }
                    }

                    $name = '';
                    if ($data[ $j ][ $k ] > 0) {
                        $name = $max == $j ? $captions[ $k ] . ' (' . $data[ $j ][ $k ] . ')' : '';
                    }
                    if (!$name && ($data[ $j ][ $k ] < 0)) {
                        $name = $min == $j ? $captions[ $k ] . ' (' . $data[ $j ][ $k ] . ')' : '';
                    }
                    $result .=
                        ('funnel_h' == $gtype ?
                            '  var p_' . $j . '_' . $k . ' = board.createElement("point", [' . $data[ $j ][ $k ] . ', ' . ($maxx - $x) . '], {' :
                            '  var p_' . $j . '_' . $k . ' = board.createElement("point", [' . $x . ', ' . $data[ $j ][ $k ] . '], {') .
                        '    highlightStrokeColor: "' . $litecolors[ $j ] . '",' .
                        '    strokeColor: "' . $colors[ $j ] . '",' .
                        '    chartStyle: "point", ' .
                        '    fixed: true,' .
                        ('bar_ml' == $gtype ? '' : 'name: "' . $name . '", ') .
                        $params .
                        ('funnel' != $gtype ? 'strokeWidth: 4, ' : 'strokeWidth: -1, style: -1, size: -1,') .
                        ('funnel_h' == $gtype ?
                            '    labeloffsets: [0, -20]' : //закомментировать для горизонтальной воронки
                            '    labeloffsets: [0, 0]') . //закомментировать для горизонтальной воронки
                        '  });';

                    //Если воронка, то добавим точку вниз и закрасим область
                    if (('funnel' == $gtype) || ('funnel_h' == $gtype)) {
                        $result .=
                            ('funnel_h' == $gtype ?
                                '  var pf_' . $j . '_' . $k . ' = board.createElement("point", [' . (-$data[ $j ][ $k ]) . ', ' . ($maxx - $x) . '], {' :
                                '  var pf_' . $j . '_' . $k . ' = board.createElement("point", [' . $x . ', ' . -$data[ $j ][ $k ] . '], {') .
                            '    chartStyle: "point", ' .
                            '    fixed: true, ' .
                            '    name: "", ' .
                            $params .
                            '    size: -1,' .
                            '    style:- 1,' .
                            '    strokeWidth: -1' .
                            '  });';
                        if ($k > 0) {
                            $result .=
                                'var poly = board.create("polygon",' .
                                '[p_' . $j . '_' . $k . ', p_' . $j . '_' . ($k - 1) . ', pf_' . $j . '_' . ($k - 1) . ', pf_' . $j . '_' . $k . '], {' .
                                'fillColor: "' . $colors[ ($k - 1) % count($colors) ] . '",' .
                                'highlightFillColor: "' . $litecolors[ ($k - 1) % count($litecolors) ] . '",' .
                                "showinfobox: true," .
                                "hasInnerPoints: true," . //При наведении мышкой на бар это будет обрабатываться
                                'withLines: false, opacity: 1, highlightFillOpacity: 1' .
                                '});' .
                                'var fs = parseFloat(board.options.text.fontSize);' .
                                'poly.infoboxTextValue = "' . $captions[ $k - 1 ] . ' (' . $data[ $j ][ $k - 1 ] . ')' . '";' .
                                ('funnel_h' == $gtype ?
                                    'poly.infoboxCoord = [' . ($data[ $j ][ $k - 1 ]) . '+fs*0.6/board.unitX, ' . ($maxx - $x + 1) . '-fs*0.5/board.unitY];' :
                                    'poly.infoboxCoord = [' . ($x - 1) . '+fs*0.5/board.unitX, ' . $data[ $j ][ $k - 1 ] . '+fs*1/board.unitY];');
                        }
                    }
                }
                //}
            }

            //Если мультигистограмма, то нарисуем еще и точки с caption с учётом цвета, уровня и отступа
            if ('bar_ml' == $gtype) {
                $result .= 'var fs = parseFloat(board.options.text.fontSize); ';
                $j = $i;
                for ($l = 0; $l < count($data[ $j ]); $l++) {
                    $k = 0;
                    $x1 = $coord[ $j ][ $l ]['x'];
                    $x2 = $coord[ $j ][ $l ]['x'] + 1;
                    $y1 = $coord[ $j ][ $l ]['y'];
                    $y2 = $coord[ $j ][ $l ]['y'] + $data[ $j ][ $l ];
                    $maxy2 = $y2 > $maxy2 ? $y2 : $maxy2;

                    if ($showlabels) {
                        $lbl_gr = count($groups) <= 3 ? 0 : count($groups) - 3;
                        if ($l == 0 || ($l > 0 && $p_data[ $l ][ $groups[ $lbl_gr ]['FieldName'] ] != $p_data[ $l - 1 ][ $groups[ $lbl_gr ]['FieldName'] ])) {
                            $result .=
                                '  var p_' . $j . '_' . $l . '_lbl = board.createElement("point", ' .
                                '    [' . $x1 . ', ' . $y1 . '-fs*2/board.unitY], {' .
                                '    fixed: true, ' .
                                '    name: "' . $p_data[ $l ][ $groups[ $lbl_gr ]['FieldName'] ] . '", ' .
                                '    size: -1, style:- 1, strokeWidth: -1' .
                                '  });';
                        }
                    }

                    $result .=
                        '  var p_' . $j . '_' . $l . '_1 = board.createElement("point", ' .
                        '    [' . $x1 . ', ' . $y1 . '], {' .
                        '    chartStyle: "point", ' .
                        '    fixed: true, ' .
                        $params .
                        '    name: "", ' .
                        '    size: -1, style:- 1, strokeWidth: -1' .
                        '  });';
                    $result .=
                        '  var p_' . $j . '_' . $l . '_2 = board.createElement("point", ' .
                        '    [' . $x1 . ', ' . $y2 . '], {' .
                        '    chartStyle: "point", ' .
                        '    fixed: true, ' .
                        $params .
                        '    name: "", ' .
                        '    size: -1, style:- 1, strokeWidth: -1' .
                        '  });';
                    $result .=
                        '  var p_' . $j . '_' . $l . '_3 = board.createElement("point", ' .
                        '    [' . $x2 . ', ' . $y2 . '], {' .
                        '    chartStyle: "point", ' .
                        '    fixed: true, ' .
                        $params .
                        '    name: "", ' .
                        '    size: -1, style:- 1, strokeWidth: -1' .
                        '  });';
                    $result .=
                        '  var p_' . $j . '_' . $l . '_4 = board.createElement("point", ' .
                        '    [' . $x2 . ', ' . $y1 . '], {' .
                        '    chartStyle: "point", ' .
                        '    fixed: true, ' .
                        $params .
                        '    name: "", ' .
                        '    size: -1, style:- 1, strokeWidth: -1' .
                        '  });';

                    $caption = '';
                    for ($m = 0; $m < count($groups); $m++) {
                        $caption .= $p_data[ $l ][ $groups[ $m ]['FieldName'] ] . '<br/>';
                    }
                    $caption .= $data[ $j ][ $l ];

                    $result .=
                        'var poly_' . $j . '_' . $l . ' = board.create("polygon", ' .
                        '[p_' . $j . '_' . $l . '_1, ' .
                        ' p_' . $j . '_' . $l . '_2, ' .
                        ' p_' . $j . '_' . $l . '_3, ' .
                        ' p_' . $j . '_' . $l . '_4], ' .
                        '{' .
                        'fillColor: "' . $group_colors[ $p_data[ $l ][ $groups[ count($groups) - 1 ]['FieldName'] ] ] . '", ' .
                        'highlightFillColor: "' . $group_litecolors[ $p_data[ $l ][ $groups[ count($groups) - 1 ]['FieldName'] ] ] . '", ' .
                        "showinfobox: true, " .
                        "hasInnerPoints: true, " . //При наведении мышкой на бар это будет обрабатываться
                        'withLines: false, opacity: 1, highlightFillOpacity: 1' .
                        '});' .
                        'poly_' . $j . '_' . $l . '.infoboxTextValue = "' . $caption . '";' .
                        'poly_' . $j . '_' . $l . '.infoboxCoord = [' . $x1 . '+fs*0.5/board.unitX, ' . $y2 . '+fs*1/board.unitY];';
                }
            }

            $i++;
        }
    }

    //Для мультидиаграммы скорректируем масштаб
    if ('bar_ml' == $gtype) {
        $search = 'boundingbox: [' . $minx . ', ' . $maxy . ', ' . $maxx . ', ' . $miny . '],';
        $replace = 'boundingbox: [' . $minx . ', ' . $maxy2 . '*1.1, ' . $maxx . ', ' . $miny . '],';
        $result = iris_str_replace($search, $replace, $result);
    }


    $result .=
        'board.unsuspendUpdate();' .
        '}' .
        'barChart();' .
        '</script>';

    return $result;
}


//Получить информацию об отчете
function GetReportInfo($p_reportid, $p_reportname = null)
{
    $con = GetConnection();
    $select_sql = 'select r.Name as name, r.Description as description, width as width ' .
        'from iris_Report r ' .
        'where r.id=:p_reportid';
    $statement = $con->prepare($select_sql);
    //$statement->bindParam(':p_reportid', $p_reportid);
    $statement->execute([
        ':p_reportid' => $p_reportid,
    ]);
    $res = $statement->fetch();

    $result['Name'] = $p_reportname ? $p_reportname : $res['name'];
    $result['Description'] = $res['description'];
    $result['Width'] = $res['width'];

    return $result;
}


//Построение отчета из шаблона
function BuildReport($p_table = '', $p_graph = '', $p_filters = '',
                     $p_report_info = null, $p_filter_fields = null,
                     $p_parameters = null)
{
    $data = [
        'encoding'    => GetDefaultEncoding(),
        'title'       => $p_report_info['Name'],
        'description' => $p_report_info['Description'],
        'width'       => $p_report_info['Width'] ? 'width: ' . $p_report_info['Width'] . 'mm;' : '',
        'parameters'  => $p_parameters,
        'params'      => $p_filter_fields,
        'table'       => $p_table,
        'graph'       => $p_graph,
        'filters'     => $p_filters,
        'date'        => date('d.m.Y'),
        'time'        => date('H:i'),
    ];

    getIncludeFiles($data, 'report');

    ob_start();
    getView('report', $data);

    return ob_get_clean();
}
