<?php

namespace Iris\Config\CRM\sections\Config;

use Config;
use DOMDocument;
use Iris\Iris;
use IrisDomain;
use Loader;
use PDO;

/**
 * Раздел "Конфигуратор". Таблица.
 */
class g_Config extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }


//  ##        #######     ###    ########
//  ##       ##     ##   ## ##   ##     ## 
//  ##       ##     ##  ##   ##  ##     ## 
//  ##       ##     ## ##     ## ##     ## 
//  ##       ##     ## ######### ##     ## 
//  ##       ##     ## ##     ## ##     ## 
//  ########  #######  ##     ## ########

    /**
     * Загрузка конфигурации из файла
     */
    public function loadFromFile($params)
    {
        $p_filename = $params['filename'];
        $Loader = Loader::getLoader();

        $con = GetConnection();
        $result = null;

        $real_filename = $Loader->getFileName($p_filename);

        // Определить полный абсолютный путь
        $root = Iris::$app->getRootDir();

        // Проверить, не выходит ли путь за пределы config
        if ($real_filename && substr($real_filename, 0, strlen($root)) !== $root) {
            $result['message'] = json_convert('Запрещается указывать файлы за пределами каталога конфигурации Iris CRM');

            return $result;
        }

        // Существует ли файл
        if (!($real_filename && is_file($real_filename))) {
            $result['message'] = json_convert('Файл "' . $real_filename . '" не найден');

            return $result;
        }

        // Определение, куда грузить
        $local_name = $p_filename;
        $config_type = substr($local_name, 0, strpos($local_name, '/'));
        $config_code = null;
        $config_type_number = null;

        switch ($config_type) {
            case 'sections':
                $config_code = substr($local_name, strlen($config_type) + 1,
                    strrpos($local_name, '/') - strlen($config_type) - 1);
                $config_type_number = 1;
                break;

            case 'dictionary':
                $config_code = substr($local_name, strlen($config_type) + 1,
                    strrpos($local_name, '.') - strlen($config_type) - 1);
                $config_type_number = 2;
                break;

            case 'common':
                $config_code = substr($local_name, strlen($config_type . '/Sections') + 1,
                    strrpos($local_name, '/') - strlen($config_type . '/Sections') - 1);
                $config_type_number = 3;
                break;
        }

        // Проверка, есть ли уже такая запись в описании конфигуратора
        $cmd = $con->prepare('select id ' .
            'from iris_config ' .
            'where type = :type and code = :code');
        $cmd->execute([
            ':type' => $config_type_number,
            ':code' => $config_code,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $section_id = null;

        // Если есть, то сотрём
        if ($res) {
            $section_id = $res[0]['id'];
            $this->DeleteConfigElement($section_id, $con);
        }
        // Иначе инициализируем (создадим пустую записись о конфигурации)
        else {
            list ($userid, $username) = GetShortUserInfo(GetUserName(), $con);
            $section_id = create_guid();
            $cmd = $con->prepare('insert into iris_config (id, createid, createdate, type, code) ' .
                'values (\'' . $section_id . '\', \'' . $userid . '\', now(), ' . $config_type_number . ', \'' . $config_code . '\')');
            $cmd->execute();
        }

        // Грузим последовательно каждый тег, каждый атрибут
        $xml = simplexml_load_file($real_filename);
        $unknown = [];

        switch (strtolower($config_type)) {
            case 'sections':
                $this->LoadSection($xml, $section_id, $config_type_number, $con, $unknown);

                $section_table_id = $this->LoadSectionTable($xml, $section_id, $con, $unknown);
                if ($section_table_id) {
                    $this->LoadSectionTableColumn($xml, $section_table_id, $con, $unknown);
                }
                $section_small_table_id = $this->LoadSectionSmallTable($xml, $section_id, $con, $unknown);
                $this->LoadSectionSmallTableColumn($xml, $section_small_table_id, $con, $unknown);
                $this->UpdateTableParams($xml, $section_table_id, $con, $unknown);
                $this->UpdateTableParams($xml, $section_small_table_id, $con, $unknown, $config_type . '_small');
                $section_card_id = $this->LoadSectionCard($xml, $section_id, $con, $unknown);
                if ($section_card_id) {
                    $this->LoadSectionCardTab($xml, $section_card_id, $con, $unknown);
                    $this->LoadSectionCardField($xml, $section_card_id, $con, $unknown);
                }
                $this->LoadSectionFilter($xml, $section_id, $section_table_id, $con, $unknown);
                $this->LoadSectionTab($xml, $section_id, $con, $unknown);
                break;

            case 'dictionary':
                $this->LoadDictionary($xml, $section_id, $config_type_number, $con, $unknown, $config_type);
                $section_small_table_id = $this->LoadSectionSmallTable($xml, $section_id, $con, $unknown, $config_type);
                $this->LoadSectionSmallTableColumn($xml, $section_small_table_id, $con, $unknown, $config_type);
                $this->UpdateTableParams($xml, $section_small_table_id, $con, $unknown, $config_type . '_small');
                $section_card_id = $this->LoadSectionCard($xml, $section_id, $con, $unknown, $config_type);
                $this->LoadSectionCardTab($xml, $section_card_id, $con, $unknown, $config_type);
                $this->LoadSectionCardField($xml, $section_card_id, $con, $unknown, $config_type);
                $this->LoadSectionTab($xml, $section_id, $con, $unknown, $config_type);
                break;

            case 'common':
                $this->LoadTab($xml, $section_id, $config_type_number, $con, $unknown, $config_type);
                $section_table_id = $this->LoadSectionTable($xml->DETAIL, $section_id, $con, $unknown, $config_type);
                $this->LoadSectionTableColumn($xml->DETAIL, $section_table_id, $con, $unknown, $config_type);
                $this->UpdateTableParams($xml, $section_table_id, $con, $unknown, $config_type);
                $section_card_id = $this->LoadSectionCard($xml->DETAIL, $section_id, $con, $unknown, $config_type);
                if ($section_card_id) {
                    $this->LoadSectionCardTab($xml->DETAIL, $section_card_id, $con, $unknown, $config_type);
                    $this->LoadSectionCardField($xml->DETAIL, $section_card_id, $con, $unknown, $config_type);
                }
                break;
        }

        $unknown_all = '';
        foreach ($unknown as $attr => $val) {
            $unknown_list = '';
            foreach ($val as $attr1 => $val1) {
                $unknown_list .= ($unknown_list ? ', ' : '') . $attr1;
            }
            $unknown_all .= ($unknown_list ? '<br>' . $attr . ': ' . $unknown_list : '');
        }
        $unknown_all = $unknown_all ? 'В XML-описании обнаружены недопустимые атрибуты<br>' . $unknown_all : '';
        $result['message'] = json_convert($unknown_all);

        return $result;
    }


//  ########  ######## ##       ######## ######## ######## 
//  ##     ## ##       ##       ##          ##    ##       
//  ##     ## ##       ##       ##          ##    ##       
//  ##     ## ######   ##       ######      ##    ######   
//  ##     ## ##       ##       ##          ##    ##       
//  ##     ## ##       ##       ##          ##    ##       
//  ########  ######## ######## ########    ##    ######## 

    /**
     * Удалить элемент конфигурации (раздел. справочник или общую вкладку)
     */
    protected function DeleteConfigElement($id, $p_con)
    {
        $con = GetConnection($p_con);

        //Список фильтров (чистим ссылки "Родительский" и "Сортировка по колонке")
        $cmd = $con->prepare('update iris_config_filter ' .
            'set sortcolumnid = null, ParentFilterID = null ' .
            'where configid = :id');
        $cmd->execute([
            ':id' => $id,
        ]);

        //Список таблиц (чистим ссылки "Сортировка по колонке" у таблиц и таблиц вкладок)
        $cmd = $con->prepare('update iris_config_table ' .
            'set SortColumnID = null ' .
            'where configid = :id or configid in (select id from iris_config where configid = :id)');
        $cmd->execute([
            ':id' => $id,
        ]);

        //Удаляем всё кроме конфига, т.к. на него могут ссылаться описания из других конфигов
        $cmd = $con->prepare('delete from iris_config_filter where configid = :id');
        $cmd->execute([
            ':id' => $id,
        ]);

        $cmd = $con->prepare('delete from iris_config_table where configid = :id');
        $cmd->execute([
            ':id' => $id,
        ]);

        $cmd = $con->prepare('delete from iris_config_card where configid = :id');
        $cmd->execute([
            ':id' => $id,
        ]);

        $cmd = $con->prepare('delete from iris_config where configid = :id');
        $cmd->execute([
            ':id' => $id,
        ]);

        //Очистим информацию в config
        $cmd = $con->prepare('select code from iris_table_column ' .
            'where code not in (\'id\', \'createid\', \'createdate\', \'code\', \'type\') ' .
            'and tableid = (select id from iris_table where code=\'iris_config\')');
        $cmd->execute();
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $update = '';
        foreach ($res as $row) {
            $update .= $update ? ', ' : '';
            $update .= $row['code'] . ' = null';
        }
        $update = 'update iris_config set ' . $update . ' where id = :id';

        $cmd = $con->prepare($update);
        $cmd->execute([
            ':id' => $id,
        ]);
    }


//  ######## ##    ## ########  ######## 
//     ##     ##  ##  ##     ## ##       
//     ##      ####   ##     ## ##       
//     ##       ##    ########  ######   
//     ##       ##    ##        ##       
//     ##       ##    ##        ##       
//     ##       ##    ##        ######## 

    /**
     * Получить значение домена / значение xml по коду домена
     */
    protected function GetConfigDomainValue($domain_code, $code, $reverse = false)
    {
        $domain = IrisDomain::getDomain($domain_code);

        if (!$reverse) {
            return $domain->get((string)$code, 'xml', 'db_value');
        }

        return $domain->get((string)$code, 'db_value', 'xml');
    }


    /**
     * Получить код типа таблички по названию типа таблички
     */
    protected function GetGridTypeNumber($code)
    {
        switch (strtolower($code)) {
            case 'grid':
                return 1;
                break;
        }

        return null;
    }


    /**
     * Преобразование yes-no в 1-0
     */
    protected function GetYesNoNumber($value, $default = null, $revert = false, $reverse = false)
    {
        if (!$reverse) {
            if (!$revert) {
                return $value == 'no' ? '0' : ($value == 'yes' ? 1 : $default);
            }
            else {
                return $value == 'no' ? 1 : ($value == 'yes' ? '0' : $default);
            }
        }
        else {
            if (!$revert) {
                return $value == '0' ? 'no' : ($value == 1 ? 'yes' : $default);
            }
            else {
                return $value == '0' ? 'yes' : ($value == 1 ? 'no' : $default);
            }
        }
    }


    /**
     * Получить список неизвестных (недопустимых) атрибутов
     */
    protected function GetUnknownAttributes($xml_tag, $allow_params, &$unknown, $section = null)
    {
        foreach ($xml_tag->attributes() as $attr => $value) {
            if (!array_search($attr, $allow_params)) {
                if ($section) {
                    $unknown[ $section ][ $attr ] = 1;
                }
                else {
                    $unknown[ $attr ] = 1;
                }
            }
        }
    }


//  ##           ######  ########  ######  ######## 
//  ##          ##    ## ##       ##    ##    ##    
//  ##          ##       ##       ##          ##    
//  ##           ######  ######   ##          ##    
//  ##                ## ##       ##          ##    
//  ##          ##    ## ##       ##    ##    ##    
//  ########     ######  ########  ######     ##    


    /**
     * Загрузка информации о разделе
     * <TAB
     *   (section_type="common" table="Таблица раздела") | section_type="special"
     *   caption="Название раздела">
     *   [<SOURCE js_source_file="u_update.js" js_function="u_update_draw();"/>]
     */
    protected function LoadSection($xml, $id, $typenumber, $p_con, &$unknown = null)
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'section_type', 'table', 'caption'];
        $con = GetConnection($p_con);
        $xml_tag = $xml->TAB;

        $tableid = null;
        if ($xml_tag['table']) {
            $tableid = GetFieldValueByFieldValue('table', 'code',
                strtolower($xml_tag['table']), 'id', $con);
        }

        $jsfile = $jsoninit = null;
        if ($xml_tag->SOURCE != null) {
            $jsfile = $xml_tag->SOURCE['js_source_file'];
            $jsoninit = $xml_tag->SOURCE['js_function'];
        }
        if ($xml_tag->DETAILS != null) {
            $show_access_detail = $this->GetYesNoNumber($xml_tag->DETAILS['hide_access_detail'], '0', true);
        }

        $fields = [
            ['Name' => 'type', 'Value' => $typenumber],
            ['Name' => 'tableid', 'Value' => $tableid],
            ['Name' => 'tablesql', 'Value' => $tableid ? null : $xml_tag['table']],
            ['Name' => 'name', 'Value' => $xml_tag['caption']],
            ['Name' => 'sectiontype', 'Value' => $this->GetConfigDomainValue('d_config_section_type', $xml_tag['section_type'])],
            ['Name' => 'jsfile', 'Value' => $jsfile],
            ['Name' => 'jsoninit', 'Value' => $jsoninit],
            ['Name' => 'showaccessdetail', 'Value' => isset($show_access_detail) ? $show_access_detail : null],
        ];

        //Проверка наличия атрибута в списке допустимых
        $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'section');

        UpdateRecord('config', $fields, $id, $con, true);
    }


//  ##           ######  ########  ######  ########    ######## ########  ##       
//  ##          ##    ## ##       ##    ##    ##          ##    ##     ## ##       
//  ##          ##       ##       ##          ##          ##    ##     ## ##       
//  ##           ######  ######   ##          ##          ##    ########  ##       
//  ##                ## ##       ##          ##          ##    ##     ## ##       
//  ##          ##    ## ##       ##    ##    ##          ##    ##     ## ##       
//  ########     ######  ########  ######     ##          ##    ########  ######## 


//Загрузка информации о таблице раздела
//<GRID 
//  [lines_count="Количество строк в таблице"]
//  [display_search="no"]
//  [hide_buttons="yes|no"]
//  [disable_dblclick="no"]
//  [ondblclick=""]
//  [is_editable="yes"]
//  [is_have_pages="no"]
//  [js_source_file="Файл со скриптом Javascript" 
//    [js_function="Функция-обработчик инициализации таблицы"]
//    [after_grid_modify="Функция-обработчик события после изменения записи"]
//    [after_delete_record="Функция-обработчик события после удаления записи"] 
//    [js_path="full"]]
//  [php_source_file="файл со скриптом PHP"
//    [php_on_prepare="Функция-обработчик события перед рисованием таблицы на сервере"]]
//  [php_replace_script="PHP скрипт для переопределения прорисовки таблицы."
//    php_replace_function="Функция для прорисовки таблицы."]
//  [sort_column="Порядковый номер колонки для сортировки"] 
//  [sort_direction="asc|desc"]
//  [caption="Заголовок окна"
//  width="Ширина окна"
//  height="Высота окна"]>
    protected function LoadSectionTable($xml, $parentid, $p_con, &$unknown = null, $config_type = 'sections_table')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'lines_count', 'display_search', 'hide_buttons',
            'disable_dblclick', 'ondblclick', 'is_editable', 'is_have_pages',
            'js_source_file', 'js_function', 'after_grid_modify', 'after_delete_record', 'js_path',
            'php_source_file', 'php_on_prepare', 'php_replace_script', 'php_replace_function',
            'sort_direction', 'sort_column',
            'caption', 'width', 'height',
            'name'];
        $con = GetConnection($p_con);

        switch ($config_type) {
            case 'sections_table':
                $xml_tag = $xml->TAB->GRID;
                $xml_filter_tag = $xml->TAB->GRID->FILTERS;
                break;

            case 'sections_table_small':
                $xml_tag = $xml->TAB->GRID_WND;
                break;

            case 'dictionary_table_small':
            case 'dictionary_small':
                $xml_tag = $xml->DICTONARY->GRID_WND;
                break;

            default:
                $xml_tag = $xml->GRID;
                break;
        }

        $type = strpos($config_type, '_small') == false ? 1 : 2;

        $id = null;
        if ($xml_tag) {
            $id = create_guid();
            $fields = [
                ['Name' => 'id', 'Value' => $id],
                ['Name' => 'configid', 'Value' => $parentid],
                ['Name' => 'type', 'Value' => $type],
                ['Name' => 'status', 'Value' => 1],
                ['Name' => 'rowcount', 'Value' => $xml_tag['lines_count']],
                ['Name' => 'showsearch', 'Value' => $this->GetYesNoNumber($xml_tag['display_search'])],
                ['Name' => 'showbuttons', 'Value' => $this->GetYesNoNumber($xml_tag['hide_buttons'], null, true)],
                ['Name' => 'letdblclick', 'Value' => $this->GetYesNoNumber($xml_tag['disable_dblclick'], null, true)],
                ['Name' => 'jsondblclick', 'Value' => $xml_tag['ondblclick']],
                ['Name' => 'edittable', 'Value' => $this->GetYesNoNumber($xml_tag['is_editable'])],
                ['Name' => 'showpages', 'Value' => $this->GetYesNoNumber($xml_tag['is_have_pages'])],
                ['Name' => 'jsfile', 'Value' => $xml_tag['js_source_file']],
                ['Name' => 'jsoninit', 'Value' => $xml_tag['js_function']],
                ['Name' => 'jsonaftermodify', 'Value' => $xml_tag['after_grid_modify']],
                ['Name' => 'jsonafterdelete', 'Value' => $xml_tag['after_delete_record']],
                ['Name' => 'jspathtype', 'Value' => $this->GetConfigDomainValue('d_config_path_type', $xml_tag['js_path'])],
                ['Name' => 'phpfile', 'Value' => $xml_tag['php_source_file']],
                ['Name' => 'phponinit', 'Value' => $xml_tag['php_on_prepare']],
                ['Name' => 'phpfilereplace', 'Value' => $xml_tag['php_replace_script']],
                ['Name' => 'phponreplace', 'Value' => $xml_tag['php_replace_function']],
                ['Name' => 'sortdirection', 'Value' => $this->GetConfigDomainValue('d_config_order_direction', $xml_tag['sort_direction'])],
                ['Name' => 'name', 'Value' => $xml_tag['caption']],
                ['Name' => 'width', 'Value' => $xml_tag['width']],
                ['Name' => 'height', 'Value' => $xml_tag['height']],
                ['Name' => 'code', 'Value' => $xml_tag['name']],
                ['Name' => 'overallsql', 'Value' => isset($xml_filter_tag['overall']) ? $xml_filter_tag['overall'] : null],
            ];
            if (!empty($xml_filter_tag['overall'])) {
                $fields[] = ['Name' => 'overallsql', 'Value' => $xml_filter_tag['overall']];
            }

            //Проверка наличия атрибута в списке допустимых
            $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, $config_type);

            InsertRecord('config_table', $fields, $con, true);
        }

        return $id;
    }


//  ##           ######  ########  ######  ########    ######## ########  ##           ######   #######  ##       
//  ##          ##    ## ##       ##    ##    ##          ##    ##     ## ##          ##    ## ##     ## ##       
//  ##          ##       ##       ##          ##          ##    ##     ## ##          ##       ##     ## ##       
//  ##           ######  ######   ##          ##          ##    ########  ##          ##       ##     ## ##       
//  ##                ## ##       ##          ##          ##    ##     ## ##          ##       ##     ## ##       
//  ##          ##    ## ##       ##    ##    ##          ##    ##     ## ##          ##    ## ##     ## ##       
//  ########     ######  ########  ######     ##          ##    ########  ########     ######   #######  ######## 


//Загрузка информации о колонках таблицы раздела
//<ITEM 
//  db_field="Имя колонки" 
//  caption="Заголовок колонки" 
//  width="Ширина колонки" 
//  row_type="common" | //Домен d_config_column_type
//  (row_type="domain" 
//    row_type_domain_name="Название домена") |
//  (row_type="fk_column" 
//    row_type_parent_table="Таблица"
//    row_type_parent_display_column="Отображаемая колонка") |
//  (row_type="fk_column_extended" 
//    row_type_joins="[join для подключения дополнительной таблицы с указанием алиаса]"
//    row_type_display_column_with_alias="Отображаемая колонка или значение"
//    [column_alias="Алиас для именования колонки"])
//  [row_datatype="string|date|datetime|int|decimal"]
//  [row_type_alias="Алиас колонки"] 
//  [display_format="none|ongrid|hidden"] 
//  [disable_sort="yes|no"]
//  [column_caption="Служебное название колонки"]
//  [total="count|sum|avg - Операция для вычисления итога"] />
    protected function LoadSectionTableColumn($xml, $parentid, $p_con, &$unknown = null, $config_type = 'sections')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'db_field', 'caption', 'width',
            'row_type', 'row_type_domain_name', 'row_type_parent_table', 'row_type_parent_display_column',
            'row_type_joins', 'row_type_display_column_with_alias', 'row_datatype', 'row_type_alias',
            'display_format', 'disable_sort', 'column_caption', 'column_alias', 'total'];
        $con = GetConnection($p_con);

        switch ($config_type) {
            case 'sections':
                $xml_columns = $xml->TAB->GRID->COLUMNS->ITEM;
                break;

            case 'sections_small':
                $xml_columns = $xml->TAB->GRID_WND->COLUMNS->ITEM;
                break;

            case 'dictionary_small':
                $xml_columns = $xml->DICTONARY->GRID_WND->COLUMNS->ITEM;
                break;

            default: //common, tabs
                $xml_columns = $xml->GRID->COLUMNS->ITEM;
        }

        $index = 1;
        if ($xml_columns) {
            foreach ($xml_columns as $xml_tag) {
                $columnid = null;
                if ($xml_tag['db_field']) {
                    $cmd = $con->prepare('select (select id ' .
                        'from iris_table_column ' .
                        'where tableid = (select c1.tableid from iris_config c1, iris_config_table c2 ' .
                        'where c1.id = c2.configid and c2.id = :configtableid) ' .
                        'and code = :code_lower) as id_lower, ' .
                        '(select id ' .
                        'from iris_table_column ' .
                        'where tableid = (select c1.tableid from iris_config c1, iris_config_table c2 ' .
                        'where c1.id = c2.configid and c2.id = :configtableid) ' .
                        'and code = :code) as id');
                    $cmd->execute([
                        ':configtableid' => $parentid,
                        ':code_lower'    => strtolower($xml_tag['db_field']),
                        ':code'          => $xml_tag['db_field'],
                    ]);
                    $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                    $columnid_lower = $res[0]['id_lower'];
                    $columnid = $res[0]['id'];
                }

                $dicttableid = null;
                if ($xml_tag['row_type_parent_table']) {
                    $dicttableid = GetFieldValueByFieldValue('table', 'code',
                        strtolower($xml_tag['row_type_parent_table']), 'id', $con);
                }

                $dictcolumnid = null;
                if ($dicttableid && $xml_tag['row_type_parent_display_column']) {
                    $cmd = $con->prepare('select id ' .
                        'from iris_table_column ' .
                        'where tableid = :tableid ' .
                        'and code = :code');
                    $cmd->execute([
                        ':tableid' => $dicttableid,
                        ':code'    => strtolower($xml_tag['row_type_parent_display_column']),
                    ]);
                    $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                    $dictcolumnid = $res[0]['id'];
                }

                $id = create_guid();
                $fields = [
                    ['Name' => 'id', 'Value' => $id],
                    ['Name' => 'configtableid', 'Value' => $parentid],
                    ['Name' => 'orderpos', 'Value' => $index],
                    ['Name' => 'columnid', 'Value' => isset($columnid_lower) ? $columnid_lower : null],
                    ['Name' => 'columnsql', 'Value' => $columnid ? null : $xml_tag['db_field']],
                    ['Name' => 'name', 'Value' => $xml_tag['caption']],
                    ['Name' => 'width', 'Value' => $xml_tag['width']],
                    ['Name' => 'columntype', 'Value' => $this->GetConfigDomainValue('d_config_column_type', $xml_tag['row_type'])],
                    ['Name' => 'domain', 'Value' => $xml_tag['row_type_domain_name']],
                    ['Name' => 'dicttableid', 'Value' => $dicttableid],
                    ['Name' => 'dicttablesql', 'Value' => $dicttableid ? null : $xml_tag['row_type_parent_table']],
                    ['Name' => 'dictcolumnid', 'Value' => $dictcolumnid],
                    ['Name' => 'dictcolumnsql', 'Value' => $dictcolumnid ? null : $xml_tag['row_type_parent_display_column']],
                    ['Name' => 'extjoin', 'Value' => $xml_tag['row_type_joins']],
                    ['Name' => 'extcolumn', 'Value' => $xml_tag['row_type_display_column_with_alias']],
                    ['Name' => 'extcolumnalias', 'Value' => $xml_tag['column_alias']],
                    ['Name' => 'datatype', 'Value' => $this->GetConfigDomainValue('d_config_column_datatype', $xml_tag['row_datatype'])],
                    ['Name' => 'alias', 'Value' => $xml_tag['row_type_alias']],
                    ['Name' => 'displayformat', 'Value' => $this->GetConfigDomainValue('d_config_column_display_format', $xml_tag['display_format'])],
                    ['Name' => 'disablesort', 'Value' => $this->GetYesNoNumber($xml_tag['disable_sort'])],
                    ['Name' => 'atributename', 'Value' => $xml_tag['column_caption']],
                    ['Name' => 'coltotal', 'Value' => $this->GetConfigDomainValue('d_config_column_total', $xml_tag['total'])],
                ];

                //Проверка наличия атрибута в списке допустимых
                $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'section_table_column');

                InsertRecord('config_table_column', $fields, $con, true);
                $index++;
            }
        }
    }


//  ##           ######  ########  ######  ########     ######     ######## ########  ##       
//  ##          ##    ## ##       ##    ##    ##       ##    ##       ##    ##     ## ##       
//  ##          ##       ##       ##          ##       ##             ##    ##     ## ##       
//  ##           ######  ######   ##          ##        ######        ##    ########  ##       
//  ##                ## ##       ##          ##             ##       ##    ##     ## ##       
//  ##          ##    ## ##       ##    ##    ##       ##    ##       ##    ##     ## ##       
//  ########     ######  ########  ######     ##        ######        ##    ########  ######## 


//Загрузка информации о таблице GRID_WND раздела
//<GRID_WND
//  lines_count="Количество строк в таблице"
//  caption="Заголовок окна"
//  width="Ширина окна"
//  height="Высота окна"
//  [sort_column="Порядковый номер колонки для сортировки"] 
//  [sort_direction="asc|desc"]>
    protected function LoadSectionSmallTable($xml, $parentid, $p_con, &$unknown = null, $config_type = 'sections_table')
    {
        return $this->LoadSectionTable($xml, $parentid, $p_con, $unknown, $config_type . '_small');
    }


//  ##           ######  ########  ######  ########     ######     ######## ########  ##           ######   #######  ##       
//  ##          ##    ## ##       ##    ##    ##       ##    ##       ##    ##     ## ##          ##    ## ##     ## ##       
//  ##          ##       ##       ##          ##       ##             ##    ##     ## ##          ##       ##     ## ##       
//  ##           ######  ######   ##          ##        ######        ##    ########  ##          ##       ##     ## ##       
//  ##                ## ##       ##          ##             ##       ##    ##     ## ##          ##       ##     ## ##       
//  ##          ##    ## ##       ##    ##    ##       ##    ##       ##    ##     ## ##          ##    ## ##     ## ##       
//  ########     ######  ########  ######     ##        ######        ##    ########  ########     ######   #######  ######## 


//Загрузка информации о колонках таблицы GRID_WND раздела
    protected function LoadSectionSmallTableColumn($xml, $parentid, $p_con, &$unknown = null, $config_type = 'sections')
    {
        $this->LoadSectionTableColumn($xml, $parentid, $p_con, $unknown, $config_type . '_small');
    }


//  ##           ######  ########  ######  ########    ######## #### ##       ######## ######## ########  
//  ##          ##    ## ##       ##    ##    ##       ##        ##  ##          ##    ##       ##     ## 
//  ##          ##       ##       ##          ##       ##        ##  ##          ##    ##       ##     ## 
//  ##           ######  ######   ##          ##       ######    ##  ##          ##    ######   ########  
//  ##                ## ##       ##          ##       ##        ##  ##          ##    ##       ##   ##   
//  ##          ##    ## ##       ##    ##    ##       ##        ##  ##          ##    ##       ##    ##  
//  ########     ######  ########  ######     ##       ##       #### ########    ##    ######## ##     ## 


//Загрузка информации о фильтрах раздела
//<ITEM 
//  caption="Заголовок фильтра" 
//  [item_style="Стиль фильтра (CSS)"] 
//  [sort_direction="desc"]
//  (where_clause="Условие фильтра (SQL)"
//    [default_selected="yes|no"]
//    [sort_column="1"]) |
//  ([auto_table="Название таблицы в БД"]
//    [auto_filter_column="Название колонки в БД"]
//    [auto_display_column="Название колонки в БД"]
//    [auto_sort_column="Название колонки в БД"]
//    [auto_where_clause="Условие фильтра (SQL)"])
//    [auto_value_selected="Значение, которое будет выбрано при открытии раздела"]
//    [values_where_clause="Условие фильтрации значений для автофильтра (SQL)"]) />
    protected function LoadSectionFilter($xml, $parentid, $gridid, $p_con, &$unknown = null, $parentfilterid = null, $index = 1)
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'caption', 'item_style', 'sort_direction',
            'where_clause', 'default_selected', 'sort_column',
            'auto_table', 'auto_filter_column', 'auto_display_column', 'auto_sort_column',
            'auto_where_clause', 'auto_value_selected', 'values_where_clause',
            'class', 'title', 'field', 'default'];
        $con = GetConnection($p_con);

        //Идём с рекурсией
        $items = $parentfilterid ? $xml : $xml->TAB->GRID->FILTERS->ITEM;
        foreach ($items as $xml_tag) {
            $id = create_guid();

            $tableid = null;
            if ($xml_tag['auto_table']) {
                $tableid = GetFieldValueByFieldValue('table', 'code',
                    strtolower($xml_tag['auto_table']), 'id', $con);
            }

            $sortcolumnid = null;
            if ($xml_tag['sort_column']) {
                $cmd = $con->prepare('select id ' .
                    'from iris_config_table_column ' .
                    'where configtableid = :tableid ' .
                    'and orderpos = :orderpos');
                $cmd->execute([
                    ':tableid'  => $gridid,
                    ':orderpos' => strtolower($xml_tag['sort_column']),
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $sortcolumnid = $res[0]['id'];
            }

            $autofiltercolumnid = null;
            if ($tableid && $xml_tag['auto_filter_column']) {
                $cmd = $con->prepare('select id ' .
                    'from iris_table_column ' .
                    'where tableid = :tableid ' .
                    'and code = :code');
                $cmd->execute([
                    ':tableid' => $tableid,
                    ':code'    => strtolower($xml_tag['auto_filter_column']),
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $autofiltercolumnid = $res[0]['id'];
            }

            $autodisplaycolumnid = null;
            if ($tableid && $xml_tag['auto_display_column']) {
                $cmd = $con->prepare('select id ' .
                    'from iris_table_column ' .
                    'where tableid = :tableid ' .
                    'and code = :code');
                $cmd->execute([
                    ':tableid' => $tableid,
                    ':code'    => strtolower($xml_tag['auto_display_column']),
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $autodisplaycolumnid = $res[0]['id'];
            }

            $autosortcolumnid = null;
            if ($tableid && $xml_tag['auto_sort_column']) {
                $cmd = $con->prepare('select id ' .
                    'from iris_table_column ' .
                    'where tableid = :tableid ' .
                    'and code = :code');
                $cmd->execute([
                    ':tableid' => $tableid,
                    ':code'    => strtolower($xml_tag['auto_sort_column']),
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $autosortcolumnid = $res[0]['id'];
            }

            $fieldid = null;
            if ($xml_tag['field']) {
                $cmd = $con->prepare('select id ' .
                    'from iris_config_card_field ' .
                    'where configcardid = (select id from iris_config_card where configid = :configid) ' .
                    'and columnsql = :code');
                $cmd->execute([
                    ':configid' => $parentid,
                    ':code'     => (string)$xml_tag['field'],
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $fieldid = $res[0]['id'];
            }

            $fields = [
                ['Name' => 'id', 'Value' => $id],
                ['Name' => 'configid', 'Value' => $parentid],
                ['Name' => 'parentfilterid', 'Value' => $parentfilterid],
                ['Name' => 'orderpos', 'Value' => $index],
                ['Name' => 'filtertype', 'Value' => $xml_tag['where_clause'] ? 1 : 2],
                ['Name' => 'name', 'Value' => $xml_tag['caption']],
                ['Name' => 'stylecss', 'Value' => $xml_tag['item_style']],
                ['Name' => 'sortdirection', 'Value' => $this->GetConfigDomainValue('d_config_order_direction', $xml_tag['sort_direction'])],
                ['Name' => 'filtersql', 'Value' => $xml_tag['where_clause']],
                ['Name' => 'isdefault', 'Value' => $this->GetYesNoNumber($xml_tag['default_selected'])],
                ['Name' => 'sortcolumnid', 'Value' => $sortcolumnid],
                ['Name' => 'autotableid', 'Value' => $tableid],
                ['Name' => 'autocolumnid', 'Value' => $autofiltercolumnid],
                ['Name' => 'autodisplaycolumnid', 'Value' => $autodisplaycolumnid],
                ['Name' => 'autosortcolumnid', 'Value' => $autosortcolumnid],
                ['Name' => 'autofiltersql', 'Value' => $xml_tag['auto_where_clause']],
                ['Name' => 'autoselected', 'Value' => $xml_tag['auto_value_selected']],
                ['Name' => 'autofilterfilter', 'Value' => $xml_tag['values_where_clause']],
                ['Name' => 'classcss', 'Value' => $xml_tag['class']],
                ['Name' => 'title', 'Value' => $xml_tag['title']],
                ['Name' => 'defaultvalue', 'Value' => $xml_tag['default']],
                ['Name' => 'fieldid', 'Value' => $fieldid],
            ];

            //Проверка наличия атрибута в списке допустимых
            $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'section_filter');

            InsertRecord('config_filter', $fields, $con, true);
            $index++;

            //Рекурсия
            $index = $this->LoadSectionFilter($xml_tag->ITEM, $parentid, $gridid, $p_con, $unknown, $id, $index);
        }

        return $index;
    }


//  ##     ##    ######## ########  ##          ########     ###    ########     ###    ##     ##  ######  
//  ##     ##       ##    ##     ## ##          ##     ##   ## ##   ##     ##   ## ##   ###   ### ##    ## 
//  ##     ##       ##    ##     ## ##          ##     ##  ##   ##  ##     ##  ##   ##  #### #### ##       
//  ##     ##       ##    ########  ##          ########  ##     ## ########  ##     ## ## ### ##  ######  
//  ##     ##       ##    ##     ## ##          ##        ######### ##   ##   ######### ##     ##       ## 
//  ##     ##       ##    ##     ## ##          ##        ##     ## ##    ##  ##     ## ##     ## ##    ## 
//   #######        ##    ########  ########    ##        ##     ## ##     ## ##     ## ##     ##  ######  


//  [sort_column="Порядковый номер колонки для сортировки"] 
//  [sort_direction="asc|desc"]>
    protected function UpdateTableParams($xml, $section_table_id, $p_con, &$unknown, $config_type = 'sections')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        //$allow_params = array('', 'sort_column', 'sort_direction');
        $con = GetConnection($p_con);

        switch ($config_type) {
            case 'sections':
                $xml_tag = $xml->TAB->GRID;
                break;

            case 'sections_small':
                $xml_tag = $xml->TAB->GRID_WND;
                break;

            case 'dictionary_small':
                $xml_tag = $xml->DICTONARY->GRID_WND;
                break;

            default:
                $xml_tag = $xml->GRID;
                break;
        }

        $tableid = null;
        if ($xml_tag['auto_table']) {
            $tableid = GetFieldValueByFieldValue('config_table', 'code',
                strtolower($xml_tag['auto_table']), 'id', $con);
        }

        $sortcolumnid = null;
        if ($xml_tag['sort_column']) {
            $cmd = $con->prepare('select id ' .
                'from iris_config_table_column ' .
                'where configtableid = :configtableid ' .
                'and orderpos = :orderpos');
            $cmd->execute([
                ':configtableid' => $section_table_id,
                ':orderpos'      => strtolower($xml_tag['sort_column']),
            ]);
            $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
            $sortcolumnid = $res[0]['id'];
        }

        $fields = [
            ['Name' => 'sortcolumnid', 'Value' => $sortcolumnid],
            //array('Name' => 'sortdirection', 'Value' => GetConfigDomainValue('d_config_order_direction', $xml_tag['sort_direction'])),
        ];


        UpdateRecord('config_table', $fields, $section_table_id, $con, true);
    }


//  ##           ######  ########  ######  ########     ######     ###    ########  ########  
//  ##          ##    ## ##       ##    ##    ##       ##    ##   ## ##   ##     ## ##     ## 
//  ##          ##       ##       ##          ##       ##        ##   ##  ##     ## ##     ## 
//  ##           ######  ######   ##          ##       ##       ##     ## ########  ##     ## 
//  ##                ## ##       ##          ##       ##       ######### ##   ##   ##     ## 
//  ##          ##    ## ##       ##    ##    ##       ##    ## ##     ## ##    ##  ##     ## 
//  ########     ######  ########  ######     ##        ######  ##     ## ##     ## ########  


//Загрузка информации о карточке раздела
//<EDITCARD
//  [name="Код карточки"]
//  caption="Заголовок карточки"
//  width="Ширина карточки"
//  height="Высота карточки"
//  layout="Расположение элементов"
//  [draw_extra_button="yes"]
//  [show_card_top_panel="yes|no"]
//  [show_card_details="yes|no"]
//  [js_source_file="Файл со скриптом Javascript"
//    [js_function="Функция-обработчик инициализации карточки"]
//    [on_after_save="Функция-обработчик события после изменения записи"]
//    [js_path="full"]]
//  [php_source_file="файл со скриптом PHP"
//    [php_on_prepare="Функция-обработчик события перед рисованием карточки на сервере"]
//    [php_on_before_post="Функция-обработчик события перед сохранением карточки на сервере"]
//    [php_on_after_post="Функция-обработчик события после сохранения карточки на сервере"]]
//  [parent_card_source="grid"
//    parent_card_name="Код раздела с описанием карточки"]>
    protected function LoadSectionCard($xml, $parentid, $p_con, &$unknown = null, $config_type = 'sections')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'name', 'caption', 'width', 'height', 'layout',
            'draw_extra_button', 'show_card_top_panel', 'show_card_details',
            'js_source_file', 'js_function', 'on_after_save', 'js_path',
            'php_source_file', 'php_on_prepare', 'php_on_before_post', 'php_on_after_post',
            'parent_card_source', 'parent_card_name'];
        $con = GetConnection($p_con);
        $xml_tag = $config_type == 'sections' ? $xml->TAB->EDITCARD
            : ($config_type == 'dictionary' ? $xml->DICTONARY->EDITCARD : $xml->EDITCARD);

        $cardconfigid = null;
        $configtypeid = $this->GetGridTypeNumber(strtolower($xml_tag['parent_card_source']));
        if ($xml_tag['parent_card_name']) {
            $cmd = $con->prepare('select id ' .
                'from iris_config ' .
                'where type = :type ' .
                'and code = :code');
            $cmd->execute([
                ':type' => $configtypeid,
                ':code' => $xml_tag['parent_card_name'],
            ]);
            $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
            $cardconfigid = isset($res[0]['id']) ? $res[0]['id'] : null;

            //Если этого элемента конфигурации ещё нет, то его надо вставить (пока как пустышку)
            if (!$cardconfigid) {
                $cardconfigid = create_guid();
                InsertRecord('config', [
                    ['Name' => 'id', 'Value' => $cardconfigid],
                    //array('Name' => 'configid', 'Value' => $parentid),
                    ['Name' => 'type', 'Value' => $configtypeid],
                    ['Name' => 'code', 'Value' => $xml_tag['parent_card_name']],
                ], $con, true);
            }
        }

        $id = create_guid();
        $fields = [
            ['Name' => 'id', 'Value' => $id],
            ['Name' => 'configid', 'Value' => $parentid],
            ['Name' => 'code', 'Value' => $xml_tag['name']],
            ['Name' => 'name', 'Value' => $xml_tag['caption']],
            ['Name' => 'width', 'Value' => $xml_tag['width']],
            ['Name' => 'height', 'Value' => $xml_tag['height']],
            ['Name' => 'saveadd', 'Value' => $this->GetYesNoNumber($xml_tag['draw_extra_button'])],
            ['Name' => 'displaypanel', 'Value' => $this->GetYesNoNumber($xml_tag['show_card_top_panel'])],
            ['Name' => 'displaytabs', 'Value' => $this->GetYesNoNumber($xml_tag['show_card_details'])],
            ['Name' => 'jsfile', 'Value' => $xml_tag['js_source_file']],
            ['Name' => 'jsoninit', 'Value' => $xml_tag['js_function']],
            ['Name' => 'jsonaftersave', 'Value' => $xml_tag['on_after_save']],
            ['Name' => 'jspathtype', 'Value' => $this->GetConfigDomainValue('d_config_path_type', $xml_tag['js_path'])],
            ['Name' => 'phpfile', 'Value' => $xml_tag['php_source_file']],
            ['Name' => 'phponinit', 'Value' => $xml_tag['php_on_prepare']],
            ['Name' => 'phponbeforesave', 'Value' => $xml_tag['php_on_before_post']],
            ['Name' => 'phponaftersave', 'Value' => $xml_tag['php_on_after_post']],
            ['Name' => 'CardConfigID', 'Value' => $cardconfigid],
        ];

        //Проверка наличия атрибута в списке допустимых
        $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'section_card');

        InsertRecord('config_card', $fields, $con, true);

        return $id;
    }


//  ##           ######  ########  ######  ########     ######     ###    ########  ########     ########    ###    ########  
//  ##          ##    ## ##       ##    ##    ##       ##    ##   ## ##   ##     ## ##     ##       ##      ## ##   ##     ## 
//  ##          ##       ##       ##          ##       ##        ##   ##  ##     ## ##     ##       ##     ##   ##  ##     ## 
//  ##           ######  ######   ##          ##       ##       ##     ## ########  ##     ##       ##    ##     ## ########  
//  ##                ## ##       ##          ##       ##       ######### ##   ##   ##     ##       ##    ######### ##     ## 
//  ##          ##    ## ##       ##    ##    ##       ##    ## ##     ## ##    ##  ##     ##       ##    ##     ## ##     ## 
//  ########     ######  ########  ######     ##        ######  ##     ## ##     ## ########        ##    ##     ## ########  


//Загрузка информации о закладках карточки раздела
//<TAB
//  caption="Название закладки"
//  rows="Количество строк на вкладке"
    protected function LoadSectionCardTab($xml, $parentid, $p_con, &$unknown = null, $config_type = 'sections')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'caption', 'rows'];
        $con = GetConnection($p_con);

        $xml_cardtabs = $config_type == 'sections' ? $xml->TAB->EDITCARD->TABS
            : ($config_type == 'dictionary' ? $xml->DICTONARY->EDITCARD->TABS : $xml->EDITCARD->TABS);

        $index = 1;
        if ($xml_cardtabs) {
            foreach ($xml_cardtabs->TAB as $xml_tag) {
                $id = create_guid();
                $fields = [
                    ['Name' => 'id', 'Value' => $id],
                    ['Name' => 'configcardid', 'Value' => $parentid],
                    ['Name' => 'name', 'Value' => $xml_tag['caption']],
                    ['Name' => 'rowcount', 'Value' => $xml_tag['rows']],
                    ['Name' => 'orderpos', 'Value' => $index],
                ];

                //Проверка наличия атрибута в списке допустимых
                $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'section_card_tab');

                InsertRecord('config_card_tab', $fields, $con, true);
                $index++;
            }
        }
    }


//  ##           ######  ########  ######  ########     ######     ###    ########  ########     ######## ##       ########  
//  ##          ##    ## ##       ##    ##    ##       ##    ##   ## ##   ##     ## ##     ##    ##       ##       ##     ## 
//  ##          ##       ##       ##          ##       ##        ##   ##  ##     ## ##     ##    ##       ##       ##     ## 
//  ##           ######  ######   ##          ##       ##       ##     ## ########  ##     ##    ######   ##       ##     ## 
//  ##                ## ##       ##          ##       ##       ######### ##   ##   ##     ##    ##       ##       ##     ## 
//  ##          ##    ## ##       ##    ##    ##       ##    ## ##     ## ##    ##  ##     ##    ##       ##       ##     ## 
//  ########     ######  ########  ######     ##        ######  ##     ## ##     ## ########     ##       ######## ########  


//Загрузка информации о колонке карточки раздела
//<FIELD
//  (
//    elem_type="spacer" | 
//    (elem_type="splitter" caption="Заголовок поля" [small="no"]) |
//    (elem_type="button"
//      caption="Текст на кнопке" 
//      (method="Название метода-обработчика" | 
//        onclick="Обработчик. Вместо onclick желательно использовать method.")
//      [code="Идентификатор кнопки. В карточке к нему будет добавлен префикс '_'"]
//      [align="left|middle|right - выравнивание кнопки, по умолчанию - left"]
//      [width="Ширина - по умолчанию 100%"]) |
//    (elem_type="detail"
//      code="Код вкладки (вкладка должна быть описана в DETAIL)"
//      [height="Высота"]) |
//    (elem_type="matrix"
//      code="Код вкладки (вкладка должна быть описана в DETAIL)") |
//    (
//      (elem_type="email|url" 
//        datatype="string" 
//        row_type="common") | 
//      (elem_type="phone" 
//        datatype="string" 
//        [row_type="common"]
//        [db_field_addl="Поле для добавочного номера" 
//          [mandatory_addl="yes|no"]]) | 
//      (elem_type="textarea" 
//        datatype="string" 
//        row_type="common"
//        [textarea_rows="Количество строк в многострочном поле"]
//        [is_rich_control="yes" 
//          [toolbar_type="Mini"]]) | 
//      (elem_type="text" 
//        ((datatype="string|int|decimal" row_type="common") | 
//        (datatype="date|datetime" row_type="date") | 
//        (datatype="file" row_type="file"))) | 
//      (elem_type="lookup" 
//        datatype="id" 
//        row_type="fk_column"
//        row_type_parent_source_type="grid|dict"
//        row_type_parent_source_name="Код источника данных (таблица или справочник)"
//        row_type_parent_display_column="Колонка для отображения"
//        filter_where="условие фильтрации") |
//      (elem_type="select" 
//        (row_type="fk_column"
//          datatype="id"
//          (row_type_parent_table="Название таблицы"
//            row_type_parent_display_column="Колонка для отображения" 
//            [order_by="Название поля для сортировки"]
//            [db_field_ext="Дополнительные поля"]) |
//          row_type_sql="Условие для выбора значений в выпадающий список.") | 
//        (row_type="domain"
//          datatype="string|int|decimal|date|datetime|id"
//          row_type_domain_name="Код домена")) |
//      (elem_type="checkbox" 
//        (row_type="domain"
//          row_type_domain_name="Название домена"
//          row_type_checked_index="Целое число, порядок значения в xml-описании домена, соответствующее отмеченному чекбоксу")
//        datatype="int|string")
//      caption="Заголовок поля"
//      db_field="Название поля в БД"
//      [mandatory="yes|no"]
//      [title="Всплывющая подсказка при наведении мыши на название поля"]
//    )
//  )
//>
    protected function LoadSectionCardField($xml, $parentid, $p_con, &$unknown = null, $config_type = 'sections')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'caption', 'db_field', 'mandatory', 'small',
            'elem_type', 'datatype', 'row_type', 'textarea_rows', 'is_rich_control', 'toolbar_type',
            'row_type_parent_source_type', 'row_type_parent_source_name', 'row_type_parent_display_column', 'filter_where',
            'row_type_parent_table', 'order_by', 'db_field_ext', 'row_type_sql',
            'row_type_domain_name', 'title', 'row_type_checked_index',
            'db_field_addl', 'mandatory_addl', 'code', 'height', 'width', 'align', 'onclick', 'method'];
        $con = GetConnection($p_con);

        $xml_card = $config_type == 'sections' ? $xml->TAB->EDITCARD
            : ($config_type == 'dictionary' ? $xml->DICTONARY->EDITCARD : $xml->EDITCARD);

        $layout = str_replace(' ', '', $xml_card['layout']);
        $layout_array = explode(',', $layout);
        $rownumber = 1;
        $colnumber = 1;

        $index = 1;
        if (!$xml_card->ELEMENTS->FIELD) {
            return;
        }
        foreach ($xml_card->ELEMENTS->FIELD as $xml_tag) {
            $detailtable = $config_type == 'sections' ? $xml->TAB['table']
                : ($config_type == 'dictionary' ? $xml->DICTONARY['table'] : $xml['detail_table']);
            $columnid = null;
            $columnsql = null;
            if ($xml_tag['db_field'] && $detailtable) {
                $cmd = $con->prepare('select (select iris_table_column.id ' .
                    'from iris_table_column left join iris_table on iris_table.id = iris_table_column.tableid ' .
                    'where iris_table.code = :table_code ' .
                    'and iris_table_column.code = :column_code_lower) as id_lower, ' .
                    '(select iris_table_column.id ' .
                    'from iris_table_column left join iris_table on iris_table.id = iris_table_column.tableid ' .
                    'where iris_table.code = :table_code ' .
                    'and iris_table_column.code = :column_code) as id');
                $cmd->execute([
                    ':table_code'        => strtolower($detailtable),
                    ':column_code_lower' => strtolower($xml_tag['db_field']),
                    ':column_code'       => $xml_tag['db_field'],
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $columnid = $res[0]['id_lower'] ? $res[0]['id_lower'] : $res[0]['id'];
                $columnsql = $res[0]['id'] ? null : $xml_tag['db_field'];
            }

            $dicttableid = null;
            if (strtolower($xml_tag['row_type_parent_source_type']) == 'grid') {
                $cmd = $con->prepare('select iris_table.id ' .
                    'from iris_table left join iris_section on iris_section.id = iris_table.sectionid ' .
                    'where iris_section.code = :code');
                $cmd->execute([
                    ':code' => $xml_tag['row_type_parent_source_name'],
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $dicttableid = $res[0]['id'];
            }
            else {
                if (strtolower($xml_tag['row_type_parent_source_type']) == 'dict') {
                    $dicttableid = GetFieldValueByFieldValue('table', 'dictionary',
                        strtolower($xml_tag['row_type_parent_source_name']), 'id', $con);
                }
            }
            if (!$dicttableid && $xml_tag['row_type_parent_table']) {
                $dicttableid = GetFieldValueByFieldValue('table', 'code',
                    strtolower($xml_tag['row_type_parent_table']), 'id', $con);
            }

            $dictcolumnid = null;
            if ($xml_tag['row_type_parent_display_column']) {
                $cmd = $con->prepare('select iris_table_column.id ' .
                    'from iris_table_column ' .
                    'where tableid = :table_id ' .
                    'and code = :column_code');
                $cmd->execute([
                    ':table_id'    => $dicttableid,
                    ':column_code' => strtolower($xml_tag['row_type_parent_display_column']),
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $dictcolumnid = $res[0]['id'];
            }

            $dictorderid = null;
            if ($xml_tag['order_by']) {
                $cmd = $con->prepare('select id ' .
                    'from iris_table_column ' .
                    'where tableid = :table_id ' .
                    'and code = :column_code');
                $cmd->execute([
                    ':table_id'    => $dicttableid,
                    ':column_code' => strtolower($xml_tag['order_by']),
                ]);
                $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $dictorderid = $res[0]['id'];
            }

            $id = create_guid();
            $fields = [
                ['Name' => 'id', 'Value' => $id],
                ['Name' => 'configcardid', 'Value' => $parentid],
                ['Name' => 'orderpos', 'Value' => $index],
                ['Name' => 'name', 'Value' => $xml_tag['caption']],
                ['Name' => 'controltype', 'Value' => $this->GetConfigDomainValue('d_config_controltype', $xml_tag['elem_type'])],
                ['Name' => 'datatype', 'Value' => $this->GetConfigDomainValue('d_config_datatype', $xml_tag['datatype'])],
                ['Name' => 'fieldtype', 'Value' => $this->GetConfigDomainValue('d_config_fieldtype', $xml_tag['row_type'])],
                ['Name' => 'columnid', 'Value' => $columnid],
                ['Name' => 'columnsql', 'Value' => $columnsql],
                ['Name' => 'ismandatory', 'Value' => $this->GetYesNoNumber($xml_tag['mandatory'])],
                ['Name' => 'issmall', 'Value' => $this->GetYesNoNumber($xml_tag['small'])],
                ['Name' => 'rowcount', 'Value' => $xml_tag['textarea_rows']],
                ['Name' => 'iswysiwyg', 'Value' => $this->GetYesNoNumber($xml_tag['is_rich_control'])],
                ['Name' => 'toolbartype', 'Value' => $this->GetConfigDomainValue('d_config_field_toolbar_type', $xml_tag['toolbar_type'])],

                ['Name' => 'dicttableid', 'Value' => $dicttableid],
                ['Name' => 'dictcolumnid', 'Value' => $dictcolumnid],
                ['Name' => 'dictfiltersql', 'Value' => $xml_tag['filter_where']],

                ['Name' => 'dicttableid', 'Value' => $dicttableid],
                ['Name' => 'listsortcolumnid', 'Value' => $dictorderid],
                ['Name' => 'listextfields', 'Value' => $xml_tag['db_field_ext']],
                ['Name' => 'listsql', 'Value' => $xml_tag['row_type_sql']],
                ['Name' => 'domain', 'Value' => $xml_tag['row_type_domain_name']],
                ['Name' => 'title', 'Value' => $xml_tag['title']],
                ['Name' => 'checkedindex', 'Value' => $xml_tag['row_type_checked_index']],
                ['Name' => 'fieldext', 'Value' => $xml_tag['db_field_addl']],
                ['Name' => 'ismandatoryext', 'Value' => $this->GetYesNoNumber($xml_tag['mandatory_addl'])],

                ['Name' => 'rownumber', 'Value' => $rownumber],
                ['Name' => 'colnumber', 'Value' => $colnumber],

                ['Name' => 'code', 'Value' => $xml_tag['code']],
                ['Name' => 'height', 'Value' => $xml_tag['height']],
                ['Name' => 'width', 'Value' => $xml_tag['width']],
                ['Name' => 'align', 'Value' => $this->GetConfigDomainValue('d_config_field_align', $xml_tag['align'])],
                ['Name' => 'onclick', 'Value' => $xml_tag['onclick']],
                ['Name' => 'method', 'Value' => $xml_tag['method']],
            ];

            //Проверка наличия атрибута в списке допустимых
            $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'section_card_field');

            InsertRecord('config_card_field', $fields, $con, true);

            $index++;

            //Строка, столбец
            if ((int)$layout_array[ $rownumber - 1 ] == $colnumber) {
                $rownumber++;
                $colnumber = 1;
            }
            else {
                $colnumber++;
            }
        }
    }


//  ##           ######  ########  ######  ########    ########    ###    ########  
//  ##          ##    ## ##       ##    ##    ##          ##      ## ##   ##     ## 
//  ##          ##       ##       ##          ##          ##     ##   ##  ##     ## 
//  ##           ######  ######   ##          ##          ##    ##     ## ########  
//  ##                ## ##       ##          ##          ##    ######### ##     ## 
//  ##          ##    ## ##       ##    ##    ##          ##    ##     ## ##     ## 
//  ########     ######  ########  ######     ##          ##    ##     ## ########  


//Загрузка информации о вкладках раздела
//<DETAIL
//  caption="Заголовок вкладки"
//  [showoncard="yes|no"]
//  [showinsection="yes|no"] 
//  name="Код вкладки" 
//  detail_fk_column="Колонка-связка с родительской таблицей" 
//  [detail_bound_clause="Условие связки вкладки с родительской записью"]
//  (external="yes" 
//  detail_file="Файл с описанием вкладки") |
//  detail_table="Таблица вкладки">
    protected function LoadSectionTab($xml, $parentid, $p_con, &$unknown = null, $config_type = 'sections')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'caption', 'showoncard', 'showinsection',
            'name', 'detail_fk_column', 'detail_bound_clause',
            'external', 'detail_file', 'detail_table'];
        $con = GetConnection($p_con);

        $xml_details = $config_type == 'sections' ? $xml->TAB->DETAILS : $xml->DICTONARY->DETAILS;
        $index = 1;
        if ($xml_details) {
            foreach ($xml_details->DETAIL as $xml_tag) {

                $tableid = null;
                if ($xml_tag['detail_table']) {
                    $tableid = GetFieldValueByFieldValue('table', 'code',
                        strtolower($xml_tag['detail_table']), 'id', $con);
                }

                $linkcolumnid = null;
                $linkcolumnid_lower = null;
                if ($tableid && $xml_tag['detail_fk_column']) {
                    $cmd = $con->prepare('select (select id ' .
                        'from iris_table_column ' .
                        'where tableid = :tableid ' .
                        'and code = :code_lower) as id_lower, ' .
                        '(select id ' .
                        'from iris_table_column ' .
                        'where tableid = :tableid ' .
                        'and code = :code) as id');
                    $cmd->execute([
                        ':tableid'    => $tableid,
                        ':code_lower' => strtolower($xml_tag['detail_fk_column']),
                        ':code'       => $xml_tag['detail_fk_column'],
                    ]);
                    $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
                    $linkcolumnid_lower = $res[0]['id_lower'];
                    $linkcolumnid = $res[0]['id'];
                }

                $id = create_guid();
                $fields = [
                    ['Name' => 'id', 'Value' => $id],
                    ['Name' => 'configid', 'Value' => $parentid],
                    ['Name' => 'type', 'Value' => 4], //4 - вкладка, см домен d_config_file_type_tab
                    ['Name' => 'orderpos', 'Value' => $index],
                    ['Name' => 'name', 'Value' => $xml_tag['caption']],
                    ['Name' => 'displayincard', 'Value' => $this->GetYesNoNumber($xml_tag['showoncard'])],
                    ['Name' => 'displayinsection', 'Value' => $this->GetYesNoNumber($xml_tag['showinsection'])],
                    ['Name' => 'code', 'Value' => $xml_tag['name']],
                    ['Name' => 'tableid', 'Value' => $tableid],
                    ['Name' => 'tablesql', 'Value' => $tableid ? null : $xml_tag['detail_table']],
                    ['Name' => 'parentid', 'Value' => $linkcolumnid_lower],
                    ['Name' => 'parentcolumnsql', 'Value' => $linkcolumnid ? null : $xml_tag['detail_fk_column']],
                    ['Name' => 'parentsql', 'Value' => $xml_tag['detail_bound_clause']],
                    ['Name' => 'filename', 'Value' => $xml_tag['detail_file']],
                    //external игнорируем, т.к. если filename заполнено, то и так понятно, что external
                ];

                //Проверка наличия атрибута в списке допустимых
                $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'section_tab');

                InsertRecord('config', $fields, $con, true);
                $index++;

                if (!$xml_tag['detail_file']) {
                    $tab_table_id = $this->LoadSectionTable($xml_tag, $id, $con, $unknown, $config_type . '_tab');
                    if ($tab_table_id) {
                        $this->LoadSectionTableColumn($xml_tag, $tab_table_id, $con, $unknown, $config_type . '_tab');
                    }
                    $this->UpdateTableParams($xml_tag, $tab_table_id, $con, $unknown, $config_type . '_tab');

                    $tab_card_id = $this->LoadSectionCard($xml_tag, $id, $con, $unknown, $config_type . '_tab');
                    if ($tab_card_id) {
                        $this->LoadSectionCardTab($xml_tag, $tab_card_id, $con, $unknown, $config_type . '_tab');
                        $this->LoadSectionCardField($xml_tag, $tab_card_id, $con, $unknown, $config_type . '_tab');
                    }
                }
            }
        }
    }


//  ##          ########    ###    ########  
//  ##             ##      ## ##   ##     ## 
//  ##             ##     ##   ##  ##     ## 
//  ##             ##    ##     ## ########  
//  ##             ##    ######### ##     ## 
//  ##             ##    ##     ## ##     ## 
//  ########       ##    ##     ## ########  


    /**
     * Загрузка информации об общей вкладке
     */
    protected function LoadTab($xml, $id, $typenumber, $p_con, &$unknown = null, $config_type = 'common')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'detail_table'];
        $con = GetConnection($p_con);
        $xml_tag = $xml->DETAIL;

        $tableid = null;
        if ($xml_tag['detail_table']) {
            $tableid = GetFieldValueByFieldValue('table', 'code',
                strtolower($xml_tag['detail_table']), 'id', $con);
        }

        $fields = [
            ['Name' => 'type', 'Value' => $typenumber],
            ['Name' => 'tableid', 'Value' => $tableid],
            ['Name' => 'tablesql', 'Value' => $tableid ? null : $xml_tag['detail_table']],
        ];

        //Проверка наличия атрибута в списке допустимых
        $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'common');

        UpdateRecord('config', $fields, $id, $con, true);
    }


//  ##          ########  ####  ######  ######## 
//  ##          ##     ##  ##  ##    ##    ##    
//  ##          ##     ##  ##  ##          ##    
//  ##          ##     ##  ##  ##          ##    
//  ##          ##     ##  ##  ##          ##    
//  ##          ##     ##  ##  ##    ##    ##    
//  ########    ########  ####  ######     ##    


    /**
     * Загрузка информации о справочнике
     */
    protected function LoadDictionary($xml, $id, $typenumber, $p_con, &$unknown = null, $config_type = 'dictionary')
    {
        //Список доступных атрибутов. Первый элемент '', чтобы индекс 0 не понимался за отсутствие элемента
        $allow_params = ['', 'table'];
        $con = GetConnection($p_con);
        $xml_tag = $xml->DICTONARY;
        $tableid = null;
        if ($xml_tag['table']) {
            $tableid = GetFieldValueByFieldValue('table', 'code',
                strtolower($xml_tag['table']), 'id', $con);
        }

        $fields = [
            ['Name' => 'type', 'Value' => $typenumber],
            ['Name' => 'tableid', 'Value' => $tableid],
            ['Name' => 'tablesql', 'Value' => $tableid ? null : $xml_tag['table']],
        ];

        //Проверка наличия атрибута в списке допустимых
        $this->GetUnknownAttributes($xml_tag, $allow_params, $unknown, 'common');

        UpdateRecord('config', $fields, $id, $con, true);
    }


///////////////////////////////////////////////////////

//   ######     ###    ##     ## ######## 
//  ##    ##   ## ##   ##     ## ##       
//  ##        ##   ##  ##     ## ##       
//   ######  ##     ## ##     ## ######   
//        ## #########  ##   ##  ##       
//  ##    ## ##     ##   ## ##   ##       
//   ######  ##     ##    ###    ######## 


    /**
     * Сохранение конфигурации из БД в файл
     */
    public function saveToFile($params)
    {
        $p_configid = $params['configid'];

        $Loader = Loader::getLoader();

        $debug = false;
        $dn = $debug ? '-test' : '';

        $result['message'] = null;

        $con = GetConnection();
        list ($section_code, $section_type) = GetFieldValuesByFieldValue('Config', 'id', $p_configid, ['code', 'type'], $con);
        $localname = $section_code;

        $section_type_code = $this->GetConfigDomainValue('d_config_file_type_', $section_type, true);

        $xml_text = '';
        switch ($section_type_code) {
            case 'sections':
                $localname = $section_type_code . '/' . $localname . '/structure' . $dn . '.xml';
                $xml_text = '<MENU_TAB></MENU_TAB>';
                break;

            case 'dictionary':
                $localname = $section_type_code . '/' . $localname . $dn . '.xml';
                $xml_text = '<DICT></DICT>';
                break;

            case 'common':
                $localname = $section_type_code . '/Sections/' . $localname . '/detail' . $dn . '.xml';
                $xml_text = '<EXTERNAL_DETAIL></EXTERNAL_DETAIL>';
                break;
        }

        $localname = $Loader->getNewFileName($localname);

        $filename = str_replace('\\', '/', $localname);
        //$custom_filename = GetCustomFileName($filename);
        $custom_filename = $filename;


        $dom = new DOMDocument();
        $dom->loadXML($xml_text);
        $xml = simplexml_import_dom($dom);

        switch ($section_type_code) {
            case 'sections':
                $this->SaveSection($xml, $p_configid, $con);
                $section_table_id = $this->SaveSectionTable($xml, $p_configid, 1, $con);
                if ($section_table_id) {
                    $this->SaveSectionTableColumn($xml, $section_table_id, 1, $con);
                }
                $section_small_table_id = $this->SaveSectionSmallTable($xml, $p_configid, $con);
                if ($section_small_table_id) {
                    $this->SaveSectionSmallTableColumn($xml, $section_small_table_id, $con);
                }
                $this->SaveSectionFilter($xml, $p_configid, $con);
                $section_card_id = $this->SaveSectionCard($xml, $p_configid, $con);
                if ($section_card_id) {
                    $this->SaveSectionCardTab($xml, $section_card_id, $con);
                    $this->SaveSectionCardField($xml, $section_card_id, $con);
                }
                $this->SaveSectionTab($xml, $p_configid, $con);
                break;

            case 'dictionary':
                $this->SaveDictionary($xml, $p_configid, $con);
                $section_small_table_id = $this->SaveSectionSmallTable($xml, $p_configid, $con);
                if ($section_small_table_id) {
                    $this->SaveSectionSmallTableColumn($xml, $section_small_table_id, $con);
                }
                $section_card_id = $this->SaveSectionCard($xml, $p_configid, $con);
                if ($section_card_id) {
                    $this->SaveSectionCardTab($xml, $section_card_id, $con);
                    $this->SaveSectionCardField($xml, $section_card_id, $con);
                }
                $this->SaveSectionTab($xml, $p_configid, $con);
                break;

            case 'common':
                $this->SaveExternalDetail($xml, $p_configid, $con);

                $section_table_id = $this->SaveSectionTable($xml, $p_configid, 1, $con);
                if ($section_table_id) {
                    $this->SaveSectionTableColumn($xml, $section_table_id, 1, $con);
                }
                $section_card_id = $this->SaveSectionCard($xml, $p_configid, $con);
                if ($section_card_id) {
                    $this->SaveSectionCardTab($xml, $section_card_id, $con);
                    $this->SaveSectionCardField($xml, $section_card_id, $con);
                }
                break;
        }

        $this->saveXML($xml, $custom_filename);
        //$dom_xml->save($filename);

        if ($debug) {
            $result['message'] = $p_configid;
        }
        //$result['message'] = (string)$xml->TAB['caption'];
        //$result['message'] = '1';
        return $result;
    }


//Сохранение XML в файл (с учётом русскоязычных символов)
    function saveXML($xml, $filename = null, $level = 0)
    {
        $string = $filename ? '<?xml version="1.0"?>' : '';

        $have_children = count($xml->children());

        //Начало ноды
        $string .= "\n" . str_repeat("  ", $level) . "<" . $xml->getName();

        //Сохраняем атрибуты
        foreach ($xml->attributes() as $name => $value) {
            //UTF8, ok
            $string .= ' ' . $name . '="' . htmlspecialchars((string)$value) . '"';
        }
        $string .= $have_children ? '>' : '';

        //Проходимся по нодам (рекурсия)
        foreach ($xml->children() as $child) {
            $string .= $this->saveXML($child, null, $level + 1);
        }

        //Конец ноды
        $string .= $have_children ? "\n" . str_repeat("  ", $level) . "</" . $xml->getName() . ">" : "/>";

        if ($filename) {
            file_put_contents($filename, $string);
        }
        else {
            return $string;
        }
        //file_put_contents($filename, (string)$xml->asXML());
    }


//Добавление атрибута, если его значение установлено
    function addNotNullAttributes(&$attributes, $array)
    {
        foreach ($array as $elem) {
            if ($elem[1]) {
                $attributes[ $elem[0] ] = $elem[1];
            }
        }
    }


//Добавляет атрибуты в XML
    function addAttributes(&$xml, $attributes, $doencode = false)
    {
        foreach ($attributes as $attr => $value) {
            $xml->addAttribute($attr, $doencode ? json_encode_str($value) : $value);
        }
    }


//   ######      ######  ########  ######  ######## 
//  ##    ##    ##    ## ##       ##    ##    ##    
//  ##          ##       ##       ##          ##    
//   ######      ######  ######   ##          ##    
//        ##          ## ##       ##          ##    
//  ##    ##    ##    ## ##       ##    ##    ##    
//   ######      ######  ########  ######     ##    


//Сохранение в XML информации о шапке файла конфига
//<TAB 
//  (section_type="common" table="Таблица раздела") | section_type="special"
//  caption="Название раздела">
//    [<SOURCE js_source_file="u_update.js" js_function="u_update_draw();"/>]
    function SaveSection(&$xml, $id, $p_con)
    {
        $con = GetConnection($p_con);

        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, t1.type as type, t1.sectiontype as sectiontype, ' .
            't1.name as name, t1.code as code, t2.code as tablecode, t1.tablesql as tablesql, ' .
            't1.jsfile as jsfile, t1.jsoninit as jsoninit ' .
            'from iris_config t1 left join iris_table t2 on t2.id=t1.tableid ' .
            'where t1.id = :id');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $res_row = $res[0];

        $section_type = $this->GetConfigDomainValue('d_config_section_type', $res_row['sectiontype'], true);

        //Заносим значения в XML
        $tag_tab = $xml->addChild('TAB');
        $attributes = [
            'section_type' => $section_type,
            'caption'      => $res_row['name'],
        ];
        if ($section_type == 'common') {
            $attributes['table'] = $res_row['tablecode'] . $res_row['tablesql'];
        }
        $this->addAttributes($tag_tab, $attributes, true);

        //Если нестандартный раздел, то опишим и тег SOURCE
        if ($section_type == 'special') {
            $tag_source = $tag_tab->addChild('SOURCE');
            $attributes = [
                'js_source_file' => $res_row['jsfile'],
                'js_function'    => $res_row['jsoninit'],
            ];
            $this->addAttributes($tag_source, $attributes, true);
        }
    }


//   ######     ########  ####  ######  ######## 
//  ##    ##    ##     ##  ##  ##    ##    ##    
//  ##          ##     ##  ##  ##          ##    
//   ######     ##     ##  ##  ##          ##    
//        ##    ##     ##  ##  ##          ##    
//  ##    ##    ##     ##  ##  ##    ##    ##    
//   ######     ########  ####  ######     ##    


//Сохранение в XML информации о шапке файла справочника
//<DICTONARY 
//  table="Таблица справочника">
    function SaveDictionary(&$xml, $id, $p_con)
    {
        $con = GetConnection($p_con);

        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, ' .
            't2.code as tablecode, t1.tablesql as tablesql ' .
            'from iris_config t1 ' .
            'left join iris_table t2 on t2.id=t1.tableid ' .
            'where t1.id = :id');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $res_row = $res[0];

        //Заносим значения в XML
        $tag = $xml->addChild('DICTONARY');

        $this->addNotNullAttributes($attributes, [
            ['table', $res_row['tablecode'] . $res_row['tablesql']],
        ]);

        $this->addAttributes($tag, $attributes, true);
    }


//   ######     ######## ##     ## ########    ########  ######## ######## 
//  ##    ##    ##        ##   ##     ##       ##     ## ##          ##    
//  ##          ##         ## ##      ##       ##     ## ##          ##    
//   ######     ######      ###       ##       ##     ## ######      ##    
//        ##    ##         ## ##      ##       ##     ## ##          ##    
//  ##    ##    ##        ##   ##     ##       ##     ## ##          ##    
//   ######     ######## ##     ##    ##       ########  ########    ##    


//Сохранение в XML информации о шапке файла справочника
//<DETAIL 
//  detail_table="Таблица справочника">
    function SaveExternalDetail(&$xml, $id, $p_con)
    {
        $con = GetConnection($p_con);

        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, ' .
            't2.code as tablecode, t1.tablesql as tablesql ' .
            'from iris_config t1 ' .
            'left join iris_table t2 on t2.id=t1.tableid ' .
            'where t1.id = :id');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $res_row = $res[0];

        //Заносим значения в XML
        $tag = $xml->addChild('DETAIL');

        $this->addNotNullAttributes($attributes, [
            ['detail_table', $res_row['tablecode'] . $res_row['tablesql']],
        ]);

        $this->addAttributes($tag, $attributes, true);
    }


//   ######      ######  ########  ######  ########    ######## ########  ##       
//  ##    ##    ##    ## ##       ##    ##    ##          ##    ##     ## ##       
//  ##          ##       ##       ##          ##          ##    ##     ## ##       
//   ######      ######  ######   ##          ##          ##    ########  ##       
//        ##          ## ##       ##          ##          ##    ##     ## ##       
//  ##    ##    ##    ## ##       ##    ##    ##          ##    ##     ## ##       
//   ######      ######  ########  ######     ##          ##    ########  ######## 


//Сохранение в XML информации о шапке таблицы раздела
//<GRID 
//  [lines_count="Количество строк в таблице"]
//  [display_search="no"]
//  [hide_buttons="yes|no"]
//  [disable_dblclick="no"]
//  [ondblclick=""]
//  [is_editable="yes"]
//  [is_have_pages="no"]
//  [js_source_file="Файл со скриптом Javascript" 
//    [js_function="Функция-обработчик инициализации таблицы"]
//    [after_grid_modify="Функция-обработчик события после изменения записи"]
//    [after_delete_record="Функция-обработчик события после удаления записи"] 
//    [js_path="full"]]
//  [php_source_file="файл со скриптом PHP"
//    [php_on_prepare="Функция-обработчик события перед рисованием таблицы на сервере"]]
//  [php_replace_script="PHP скрипт для переопределения прорисовки таблицы."
//    php_replace_function="Функция для прорисовки таблицы."]
//  [sort_column="Порядковый номер колонки для сортировки"] 
//  [sort_direction="asc|desc"]
//  [caption="Заголовок окна"
//  width="Ширина окна"
//  height="Высота окна"]>
    function SaveSectionTable(&$xml, $id, $type = 1, $p_con = null)
    {
        $con = GetConnection($p_con);

        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, tt.type as configtype, tt.code as configcode, ' .
            't1.type as type, t1.rowcount as rowcount, ' .
            't2.orderpos as sortcolumnnumber, t3.code as sortcolumn, t2.columnsql as sortsql, ' .
            't1.sortdirection as sortdirection, ' .
            't1.edittable as edittable, t1.letdblclick as letdblclick, t1.showsearch as showsearch, ' .
            't1.showbuttons as showbuttons, t1.showpages as showpages, ' .
            't1.jspathtype as jspathtype, t1.jsfile as jsfile, t1.jsoninit as jsoninit, ' .
            't1.jsonaftermodify as jsonaftermodify, t1.jsonafterdelete as jsonafterdelete, t1.jsondblclick as jsondblclick, ' .
            't1.phpfile as phpfile, t1.phponinit as phponinit, ' .
            't1.phpfilereplace as phpfilereplace, t1.phponreplace as phponreplace, ' .
            't1.name as name, t1.width as width, t1.height as height, ' .
            't1.code as code ' .
            'from iris_config_table t1 ' .
            'left join iris_config_table_column t2 on t2.id=t1.sortcolumnid ' .
            'left join iris_table_column t3 on t3.id=t2.columnid ' .
            'left join iris_config tt on tt.id=t1.configid ' .
            'where t1.type = :type and (t1.status is null or t1.status=1) and t1.configid = :id');
        $cmd->execute([
            ':type' => $type,
            ':id'   => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);

        //Если в базе нет инфо о таблице, то вернём null вместо id таблицы
        if (count($res) == 0) {
            return null;
        }

        $res_row = $res[0];

        //Переменные, которые вычисляются не совсем тривиально
        $show_search = $this->GetYesNoNumber($res_row['showsearch'], null, false, true);
        $hide_buttons = $this->GetYesNoNumber($res_row['showbuttons'], null, true, true);
        $disable_dblclick = $this->GetYesNoNumber($res_row['letdblclick'], null, true, true);
        $is_editable = $this->GetYesNoNumber($res_row['edittable'], null, false, true);
        $is_have_pages = $this->GetYesNoNumber($res_row['showpages'], null, false, true);
        $js_path = $this->GetConfigDomainValue('d_config_path_type', $res_row['jspathtype'], true);
        $sort_column = $res_row['sortcolumnnumber']; //$res_row['sortcolumn'].$res_row['sortsql'];
        $sort_direction = $this->GetConfigDomainValue('d_config_order_direction', $res_row['sortdirection'], true);

        //Заносим значения в XML
        $tag_name = $type == 1 ? 'GRID' : 'GRID_WND';
        $config_type_code = $this->GetConfigDomainValue('d_config_file_type_', $res_row['configtype'], true);
        if ($config_type_code == 'sections') {
            $tag_tab = $xml->TAB->addChild($tag_name);
        }
        if ($config_type_code == 'dictionary') {
            $tag_tab = $xml->DICTONARY->addChild($tag_name);
        }
        if ($config_type_code == 'common') {
            $tag_tab = $xml->DETAIL->addChild($tag_name);
        }
        if ($config_type_code == 'tab') {
            $tag_tab = $xml->addChild($tag_name);
        }
        $attributes = [];

        $this->addNotNullAttributes($attributes, [
            ['lines_count', $res_row['rowcount']],
            ['display_search', $show_search],
            ['hide_buttons', $hide_buttons],
            ['disable_dblclick', $disable_dblclick],
            ['ondblclick', $res_row['jsondblclick']],
            ['is_editable', $is_editable],
            ['is_have_pages', $is_have_pages],
            ['js_source_file', $res_row['jsfile']],
            ['js_function', $res_row['jsoninit']],
            ['after_grid_modify', $res_row['jsonaftermodify']],
            ['after_delete_record', $res_row['jsonafterdelete']],
            ['js_path', $js_path],
            ['php_source_file', $res_row['phpfile']],
            ['php_on_prepare', $res_row['phponinit']],
            ['php_replace_script', $res_row['phpfilereplace']],
            ['php_replace_function', $res_row['phponreplace']],
            ['sort_column', $sort_column],
            ['sort_direction', $sort_direction],
            ['caption', $res_row['name']],
            ['width', $res_row['width']],
            ['height', $res_row['height']],
            ['name', $res_row['code']],
        ]);

        $this->addAttributes($tag_tab, $attributes, true);

        return $res_row['id'];
    }


//   ######      ######  ########  ######  ########    ######## ########  ##           ######   #######  ##       
//  ##    ##    ##    ## ##       ##    ##    ##          ##    ##     ## ##          ##    ## ##     ## ##       
//  ##          ##       ##       ##          ##          ##    ##     ## ##          ##       ##     ## ##       
//   ######      ######  ######   ##          ##          ##    ########  ##          ##       ##     ## ##       
//        ##          ## ##       ##          ##          ##    ##     ## ##          ##       ##     ## ##       
//  ##    ##    ##    ## ##       ##    ##    ##          ##    ##     ## ##          ##    ## ##     ## ##       
//   ######      ######  ########  ######     ##          ##    ########  ########     ######   #######  ######## 


//Сохранение в XML информации о колонках таблицы раздела
//<ITEM 
//  db_field="Имя колонки" 
//  caption="Заголовок колонки" 
//  width="Ширина колонки" 
//  row_type="common" | //Домен d_config_column_type
//  (row_type="domain" 
//    row_type_domain_name="Название домена") |
//  (row_type="fk_column" 
//    row_type_parent_table="Таблица"
//    row_type_parent_display_column="Отображаемая колонка") |
//  (row_type="fk_column_extended" 
//    row_type_joins="[join для подключения дополнительной таблицы с указанием алиаса]"
//    row_type_display_column_with_alias="Отображаемая колонка или значение"
//    [column_alias="Алиас для именования колонки"])
//  [row_datatype="string|date|datetime|int|decimal"]
//  [row_type_alias="Алиас колонки"] 
//  [display_format="none|ongrid|hidden"] 
//  [disable_sort="yes|no"]
//  [column_caption="Служебное название колонки"]
//  [total="count|sum|avg - Операция для вычисления итога"] />
    function SaveSectionTableColumn(&$xml, $id, $type = 1, $p_con = null)
    {
        $con = GetConnection($p_con);

        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, tt.type as configtype, ' .
            't2.code as columncode, t1.columnsql as columnsql, t1.name as name, ' .
            't1.width as width, t1.columntype as columntype, t1.domain as domain, ' .
            't3.code as dicttablecode, t1.dicttablesql, ' .
            't4.code as dictcolumncode, t1.dictcolumnsql, ' .
            't1.extjoin as extjoin, t1.extcolumn as extcolumn, t1.extcolumnalias as extcolumnalias, ' .

            't1.datatype as datatype, t1.alias as alias, t1.displayformat as displayformat, ' .
            't1.disablesort as disablesort, t1.atributename as atributename, t1.coltotal as coltotal ' .
            'from iris_config_table_column t1 ' .
            'left join iris_table_column t2 on t2.id=t1.columnid ' .
            'left join iris_table t3 on t3.id=t1.dicttableid ' .
            'left join iris_table_column t4 on t4.id=t1.dictcolumnid ' .
            'left join iris_config_table tt1 on tt1.id=t1.configtableid ' .
            'left join iris_config tt on tt.id=tt1.configid ' .
            'where t1.configtableid = :id ' .
            'order by t1.orderpos');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);

        //Если в базе нет инфо о колонках, то вернём null
        if (count($res) == 0) {
            return null;
        }

        $config_type_code = $this->GetConfigDomainValue('d_config_file_type_', $res[0]['configtype'], true);
        $tag = null;
        if ($config_type_code == 'sections') {
            $tag = $xml->TAB;
        }
        if ($config_type_code == 'dictionary') {
            $tag = $xml->DICTONARY;
        }
        if ($config_type_code == 'common') {
            $tag = $xml->DETAIL;
        }
        if ($config_type_code == 'tab') {
            $tag = $xml;
        }
        if ($tag == null) {
            return null;
        }

        foreach ($tag->children() as $child) {
            if (($type == 1 && $child->getName() == 'GRID')
                || ($type == 2 && $child->getName() == 'GRID_WND')
            ) {
                $tag_columns = $child;
                break;
            }
        }
        $tag_columns = $tag_columns->addChild('COLUMNS');

        foreach ($res as $res_row) {
            //Переменные, которые вычисляются не совсем тривиально
            $columntype = $this->GetConfigDomainValue('d_config_column_type', $res_row['columntype'], true);

            //Заносим значения в XML
            $tag_item = $tag_columns->addChild('ITEM');
            $attributes = [];

            $this->addNotNullAttributes($attributes, [
                ['db_field', $res_row['columnsql'] ? $res_row['columnsql'] : $res_row['columncode']],
                ['caption', $res_row['name']],
                ['width', $res_row['width']],
                ['row_type', $columntype],
            ]);

            if ('domain' == $columntype) {
                $this->addNotNullAttributes($attributes, [
                    ['row_type_domain_name', $res_row['domain']],
                ]);
            }
            else {
                if ('fk_column' == $columntype) {
                    $this->addNotNullAttributes($attributes, [
                        ['row_type_parent_table', $res_row['dicttablecode'] ? $res_row['dicttablecode'] : $res_row['dicttablesql']],
                        ['row_type_parent_display_column', $res_row['dictcolumncode'] ? $res_row['dictcolumncode'] : $res_row['dictcolumnsql']],
                    ]);
                }
                else {
                    if ('fk_column_extended' == $columntype) {
                        $this->addNotNullAttributes($attributes, [
                            ['row_type_joins', $res_row['extjoin']],
                            ['row_type_display_column_with_alias', $res_row['extcolumn']],
                            ['column_alias', $res_row['extcolumnalias']],
                        ]);
                    }
                }
            }

            $this->addNotNullAttributes($attributes, [
                ['row_datatype', $this->GetConfigDomainValue('d_config_column_datatype', $res_row['datatype'], true)],
                ['row_type_alias', $res_row['alias']],
                ['display_format', $this->GetConfigDomainValue('d_config_column_display_format', $res_row['displayformat'], true)],
                ['disable_sort', $this->GetYesNoNumber($res_row['disablesort'], null, false, true)],
                ['column_caption', $res_row['atributename']],
                ['total', $this->GetConfigDomainValue('d_config_column_total', $res_row['coltotal'], true)],
            ]);

            $this->addAttributes($tag_item, $attributes, true);
        }

//  return $res_row['id'];
    }


//   ######      ######  ########  ######  ########     ######     ######## ########  ##       
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ##       ##    ##     ## ##       
//  ##          ##       ##       ##          ##       ##             ##    ##     ## ##       
//   ######      ######  ######   ##          ##        ######        ##    ########  ##       
//        ##          ## ##       ##          ##             ##       ##    ##     ## ##       
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ##       ##    ##     ## ##       
//   ######      ######  ########  ######     ##        ######        ##    ########  ######## 


//Сохранение в XML информации о шапке таблицы wnd раздела
    function SaveSectionSmallTable(&$xml, $id, $p_con = null)
    {
        return $this->SaveSectionTable($xml, $id, 2, $p_con);
    }


//   ######      ######  ########  ######  ########     ######     ######## ########  ##           ######   #######  ##       
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ##       ##    ##     ## ##          ##    ## ##     ## ##       
//  ##          ##       ##       ##          ##       ##             ##    ##     ## ##          ##       ##     ## ##       
//   ######      ######  ######   ##          ##        ######        ##    ########  ##          ##       ##     ## ##       
//        ##          ## ##       ##          ##             ##       ##    ##     ## ##          ##       ##     ## ##       
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ##       ##    ##     ## ##          ##    ## ##     ## ##       
//   ######      ######  ########  ######     ##        ######        ##    ########  ########     ######   #######  ######## 


//Сохранение в XML информации о колонках таблицы wnd раздела
    function SaveSectionSmallTableColumn(&$xml, $id, $p_con = null)
    {
        $this->SaveSectionTableColumn($xml, $id, 2, $p_con);
    }


//   ######      ######  ########  ######  ########    ######## #### ##       ######## ######## ########  
//  ##    ##    ##    ## ##       ##    ##    ##       ##        ##  ##          ##    ##       ##     ## 
//  ##          ##       ##       ##          ##       ##        ##  ##          ##    ##       ##     ## 
//   ######      ######  ######   ##          ##       ######    ##  ##          ##    ######   ########  
//        ##          ## ##       ##          ##       ##        ##  ##          ##    ##       ##   ##   
//  ##    ##    ##    ## ##       ##    ##    ##       ##        ##  ##          ##    ##       ##    ##  
//   ######      ######  ########  ######     ##       ##       #### ########    ##    ######## ##     ## 


//Сохранение в XML информации о фильтрах раздела
//<ITEM 
//  caption="Заголовок фильтра" 
//  [item_style="Стиль фильтра (CSS)"] 
//  (where_clause="Условие фильтра (SQL)"
//    [default_selected="yes|no"]
//    [sort_column="1"]
//    [sort_direction="desc"]) |
//  ([auto_table="Название таблицы в БД"]
//    [auto_filter_column="Название колонки в БД"]
//    [auto_display_column="Название колонки в БД"]
//    [auto_sort_column="Название колонки в БД"]
//    [auto_where_clause="Условие фильтра (SQL)"])
//    [auto_value_selected="Значение, которое будет выбрано при открытии раздела"]
//    [values_where_clause="Условие фильтрации значений для автофильтра (SQL)"]) />
    function SaveSectionFilter(&$xml, $id, $p_con = null)
    {
        $con = GetConnection($p_con);

        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, tt.type as configtype, ' .
            't1.name as name, t1.stylecss as stylecss, t1.filtersql as filtersql, ' .
            't1.sortdirection as sortdirection, t1.isdefault as isdefault, ' .
            't2.orderpos as sortcolumnnumber, t1.sortdirection as sortdirection, t1.hardfiltersql as hardfiltersql, ' .
            't3.code as autotable, t4.code as autofiltercolumn, t5.code as autodisplaycolumn, ' .
            't6.code as autosortcolumn, t1.autofiltersql as autofiltersql, t1.autoselected as autoselected, ' .
            't1.autofilterfilter as autofilterfilter, t1.parentfilterid as parentfilterid, ' .
            't1.classcss, t1.title, t1.defaultvalue, t7.columnsql as field ' .
            'from iris_config_filter t1 ' .
            'left join iris_config_table_column t2 on t2.id=t1.sortcolumnid ' .
            'left join iris_table t3 on t3.id=t1.autotableid ' .
            'left join iris_table_column t4 on t4.id=t1.autocolumnid ' .
            'left join iris_table_column t5 on t5.id=t1.autodisplaycolumnid ' .
            'left join iris_table_column t6 on t6.id=t1.autosortcolumnid ' .
            'left join iris_config tt on tt.id=t1.configid ' .
            'left join iris_config_card_field t7 on t7.id=t1.fieldid ' .
            'where t1.configid = :id ' .
            'order by t1.orderpos, t1.parentfilterid desc');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);

        //Если в базе нет инфо о фильтрах, то вернём null
        if (count($res) == 0) {
            return null;
        }

        $config_type_code = $this->GetConfigDomainValue('d_config_file_type_', $res[0]['configtype'], true);
        if ($config_type_code == 'sections' && $tag_filter = $xml->TAB->GRID != null) {
            $tag_filter = $xml->TAB->GRID->addChild('FILTERS');
        }
        else {
            return null;
        }


        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, ' .
            't1.overallsql as overallsql ' .
            'from iris_config_table t1 ' .
            'where t1.type = :type and (t1.status is null or t1.status=1) and t1.configid = :id');
        $cmd->execute([
            ':type' => 1,
            ':id'   => $id,
        ]);
        $res_tbl = $cmd->fetchAll(PDO::FETCH_ASSOC);
        $res_tbl_row = $res_tbl[0];
        if (!empty($res_tbl_row['overallsql'])) {
            $attributes = [];
            $this->addNotNullAttributes($attributes, [
                ['overall', $res_tbl_row['overallsql']],
            ]);
            $this->addAttributes($tag_filter, $attributes, true);
        }

        //Пересортировка $res, чтобы родительские фильтры были всегда выше
        $this->sortFilterRes($res);

        //Сохраняем фильтры
        foreach ($res as $res_row) {
            //Получение родительского фильтра, куда будем сохранять дочерний
            $parent_item = $this->getFilterItem($tag_filter, $this->getFilterIndex($res, $res_row['parentfilterid']));
            if (!$parent_item) {
                $parent_item = $tag_filter;
            }
            $tag_item = $parent_item->addChild('ITEM');

            //Заносим значения в XML
            $attributes = [];

            $this->addNotNullAttributes($attributes, [
                ['caption', $res_row['name']],
                ['item_style', $res_row['stylecss']],
                ['class', $res_row['classcss']],
                ['title', $res_row['title']],
                ['field', $res_row['field']],
                ['default', $res_row['defaultvalue']],
            ]);

            if ($res_row['filtersql']) {
                $this->addNotNullAttributes($attributes, [
                    ['where_clause', $res_row['filtersql']],
                    ['default_selected', $this->GetYesNoNumber($res_row['isdefault'], null, false, true)],
                    ['sort_column', $res_row['sortcolumnnumber']],
                    ['sort_direction', $this->GetConfigDomainValue('d_config_order_direction', $res_row['sortdirection'], true)],
                ]);
            }

            if ($res_row['autotable']) {
                $this->addNotNullAttributes($attributes, [
                    ['auto_table', $res_row['autotable']],
                    ['auto_filter_column', $res_row['autofiltercolumn']],
                    ['auto_display_column', $res_row['autodisplaycolumn']],
                    ['auto_sort_column', $res_row['autosortcolumn']],
                    ['auto_where_clause', $res_row['autofiltersql']],
                    ['auto_value_selected', $res_row['autoselected']],
                    ['values_where_clause', $res_row['autofilterfilter']],
                ]);
            }

            $this->addAttributes($tag_item, $attributes, true);
        }
    }

//Пересортировка $res, чтобы родительские фильтры были всегда выше
    function sortFilterRes(&$res)
    {
        for ($i = 0; $i < count($res) - 1; $i++) {
            for ($j = $i + 1; $j < count($res); $j++) {
                if ($res[ $i ]['parentfilterid'] == $res[ $j ]['id']) {
                    $restmp = $res[ $i ];
                    $res[ $i ] = $res[ $j ];
                    $res[ $j ] = $restmp;
                }
            }
        }
    }

//Получить сквозной порядковый номер фильтра в общем списке фильтров
    function getFilterIndex($res, $id)
    {
        if (!$id) {
            return -1;
        }
        for ($i = 0; $i < count($res); $i++) {
            if ($res[ $i ]['id'] == $id) {
                return $i;
            }
        }

        return -1;
    }

//Получить xml фильтра по сквозному порядковому номеру
    function getFilterItem($tag_filter, $index, &$current = -1)
    {
        if ($index == $current) {
            return $tag_filter;
        }
        else {
            foreach ($tag_filter as $tag_item) {
                $current++;
                if ($index == $current) {
                    return $tag_item;
                }
                $tag_subitem = $this->getFilterItem($tag_item, $index, $current);
                if ($index == $current) {
                    return $tag_subitem;
                }
            }
        }

        return null;
    }


//   ######      ######  ########  ######  ########     ######     ###    ########  ########  
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ##   ## ##   ##     ## ##     ## 
//  ##          ##       ##       ##          ##       ##        ##   ##  ##     ## ##     ## 
//   ######      ######  ######   ##          ##       ##       ##     ## ########  ##     ## 
//        ##          ## ##       ##          ##       ##       ######### ##   ##   ##     ## 
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ## ##     ## ##    ##  ##     ## 
//   ######      ######  ########  ######     ##        ######  ##     ## ##     ## ########  


//Сохранение в XML информации о карточке раздела
//<EDITCARD
//  [name="Код карточки"]
//  caption="Заголовок карточки"
//  width="Ширина карточки"
//  height="Высота карточки"
//  layout="Расположение элементов"
//  [draw_extra_button="yes"]
//  [show_card_top_panel="yes|no"]
//  [show_card_details="yes|no"]
//  [js_source_file="Файл со скриптом Javascript"
//    [js_function="Функция-обработчик инициализации карточки"]
//    [on_after_save="Функция-обработчик события после изменения записи"]
//    [js_path="full"]]
//  [php_source_file="файл со скриптом PHP"
//    [php_on_prepare="Функция-обработчик события перед рисованием карточки на сервере"]
//    [php_on_before_post="Функция-обработчик события перед сохранением карточки на сервере"]
//    [php_on_after_post="Функция-обработчик события после сохранения карточки на сервере"]]
//  [parent_card_source="grid"
//    parent_card_name="Код раздела с описанием карточки"]>
    function SaveSectionCard(&$xml, $id, $p_con = null)
    {
        $con = GetConnection($p_con);
        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, tt.type as configtype, ' .
            't1.code as code, t1.name as name, ' .
            't1.width as width, t1.height as height, ' .
            't1.saveadd as saveadd, t1.displaypanel as displaypanel, t1.displaytabs as displaytabs, ' .
            't1.jsfile as jsfile, t1.jsoninit as jsoninit, ' .
            't1.jsonaftersave as jsonaftersave, t1.jspathtype as jspathtype, ' .
            't1.phpfile as phpfile, t1.phponinit as phponinit, ' .
            't1.phponbeforesave as phponbeforesave, t1.phponaftersave as phponaftersave, ' .
            't2.code as extcardconfigcode, t2.type as extcardconfigtype ' .
            'from iris_config_card t1 ' .
            'left join iris_config t2 on t2.id=t1.cardconfigid ' .
            'left join iris_config tt on tt.id=t1.configid ' .
            'where (t1.status is null or t1.status=1) and t1.configid = :id');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);


        //Если в базе нет инфо о карточке, то вернём null вместо id карточки
        if (count($res) == 0) {
            return null;
        }

        $res_row = $res[0];

        //Получение информации о $layout
        $layout = $this->getLayout($res_row['id'], $p_con);

        //Заносим значения в XML
        $config_type_code = $this->GetConfigDomainValue('d_config_file_type_', $res_row['configtype'], true);
        if ($config_type_code == 'sections') {
            $tag = $xml->TAB->addChild('EDITCARD');
        }
        if ($config_type_code == 'dictionary') {
            $tag = $xml->DICTONARY->addChild('EDITCARD');
        }
        if ($config_type_code == 'common') {
            $tag = $xml->DETAIL->addChild('EDITCARD');
        }
        if ($config_type_code == 'tab') {
            $tag = $xml->addChild('EDITCARD');
        }
        $attributes = [];

        $this->addNotNullAttributes($attributes, [
            ['name', $res_row['code']],
            ['caption', $res_row['name']],
            ['width', $res_row['width']],
            ['height', $res_row['height']],
            ['layout', $layout],
            ['draw_extra_button', $this->GetYesNoNumber($res_row['saveadd'], null, false, true)],
            ['show_card_top_panel', $this->GetYesNoNumber($res_row['displaypanel'], null, false, true)],
            ['show_card_details', $this->GetYesNoNumber($res_row['displaytabs'], null, false, true)],
        ]);

        if ($res_row['jsfile']) {
            $this->addNotNullAttributes($attributes, [
                ['js_source_file', $res_row['jsfile']],
                ['js_function', $res_row['jsoninit']],
                ['on_after_save', $res_row['jsonaftersave']],
                ['js_path', $this->GetConfigDomainValue('d_config_path_type', $res_row['jspathtype'], true)],
            ]);
        }

        if ($res_row['phpfile']) {
            $this->addNotNullAttributes($attributes, [
                ['php_source_file', $res_row['phpfile']],
                ['php_on_prepare', $res_row['phponinit']],
                ['php_on_before_post', $res_row['phponbeforesave']],
                ['php_on_after_post', $this->GetConfigDomainValue('d_config_path_type', $res_row['phponaftersave'], true)],
            ]);
        }

        $parent_config_type = $this->GetConfigDomainValue('d_config_file_type_', $res_row['extcardconfigtype'], true);
        $parent_config_signature = null;
        if ($parent_config_type == 'sections') {
            $parent_config_signature = 'grid';
        }
        if ($parent_config_signature) {
            $this->addNotNullAttributes($attributes, [
                ['parent_card_source', $parent_config_signature],
                ['parent_card_name', $res_row['extcardconfigcode']],
            ]);
        }

        $this->addAttributes($tag, $attributes, true);

        return $res_row['id'];
    }

//Получение строки layout из описаний колонок
    function getLayout($cardid, $p_con = false)
    {
        $con = GetConnection($p_con);

        $cmd_field = $con->prepare('select max(t1.colnumber) as colcount ' .
            'from iris_config_card_field t1 ' .
            'where t1.configcardid = :id ' .
            'group by t1.rownumber ' .
            'order by t1.rownumber');
        $cmd_field->execute([
            ':id' => $cardid,
        ]);
        $res_field = $cmd_field->fetchAll(PDO::FETCH_ASSOC);

        $layout_array = [];
        foreach ($res_field as $field) {
            $layout_array[] = $field['colcount'];
        }
        $layout = implode(',', $layout_array);

        return $layout;
    }


//   ######      ######  ########  ######  ########     ######     ###    ########  ########     ########    ###    ########  
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ##   ## ##   ##     ## ##     ##       ##      ## ##   ##     ## 
//  ##          ##       ##       ##          ##       ##        ##   ##  ##     ## ##     ##       ##     ##   ##  ##     ## 
//   ######      ######  ######   ##          ##       ##       ##     ## ########  ##     ##       ##    ##     ## ########  
//        ##          ## ##       ##          ##       ##       ######### ##   ##   ##     ##       ##    ######### ##     ## 
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ## ##     ## ##    ##  ##     ##       ##    ##     ## ##     ## 
//   ######      ######  ########  ######     ##        ######  ##     ## ##     ## ########        ##    ##     ## ########  


//Сохранение в XML информации о закладках карточки раздела
//[<TABS>
//    <TAB
//      caption="Название закладки"
//      rows="Количество строк на закладке"/>
//  </TABS>]
    function SaveSectionCardTab(&$xml, $id, $p_con = null)
    {
        $con = GetConnection($p_con);
        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, tt.type as configtype, ' .
            't1.name as name, t1.orderpos as orderpos, t1.rowcount as rowcount ' .
            'from iris_config_card_tab t1 ' .
            'left join iris_config_card tt1 on tt1.id=t1.configcardid ' .
            'left join iris_config tt on tt.id=tt1.configid ' .
            'where t1.configcardid = :id ' .
            'order by t1.orderpos');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);

        //Если в базе нет инфо о закладке, то сразу выход
        if (count($res) == 0) {
            return null;
        }

        //Заносим значения в XML
        $config_type_code = $this->GetConfigDomainValue('d_config_file_type_', $res[0]['configtype'], true);
        $tag = null;
        if ($config_type_code == 'sections') {
            $tag = $xml->TAB->EDITCARD->addChild('TABS');
        }
        if ($config_type_code == 'dictionary') {
            $tag = $xml->DICTONARY->EDITCARD->addChild('TABS');
        }
        if ($config_type_code == 'common') {
            $tag = $xml->DETAIL->EDITCARD->addChild('TABS');
        }
        if ($config_type_code == 'tab') {
            $tag = $xml->EDITCARD->addChild('TABS');
        }
        if ($tag == null) {
            return null;
        }

        foreach ($res as $res_row) {
            $tag_tab = $tag->addChild('TAB');
            $attributes = [];
            $this->addNotNullAttributes($attributes, [
                ['caption', $res_row['name']],
                ['rows', $res_row['rowcount']],
            ]);

            $this->addAttributes($tag_tab, $attributes, true);
        }
    }


//   ######      ######  ########  ######  ########     ######     ###    ########  ########     ######## ##       ########  
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ##   ## ##   ##     ## ##     ##    ##       ##       ##     ## 
//  ##          ##       ##       ##          ##       ##        ##   ##  ##     ## ##     ##    ##       ##       ##     ## 
//   ######      ######  ######   ##          ##       ##       ##     ## ########  ##     ##    ######   ##       ##     ## 
//        ##          ## ##       ##          ##       ##       ######### ##   ##   ##     ##    ##       ##       ##     ## 
//  ##    ##    ##    ## ##       ##    ##    ##       ##    ## ##     ## ##    ##  ##     ##    ##       ##       ##     ## 
//   ######      ######  ########  ######     ##        ######  ##     ## ##     ## ########     ##       ######## ########  


//Сохранение в XML информации о полях карточки раздела
//<FIELD
//  (
//    elem_type="spacer" | 
//    (elem_type="splitter" caption="Заголовок поля" [small="no"]) |
//    (elem_type="button"
//      caption="Текст на кнопке" 
//      (method="Название метода-обработчика" | 
//        onclick="Обработчик. Вместо onclick желательно использовать method.")
//      [code="Идентификатор кнопки. В карточке к нему будет добавлен префикс '_'"]
//      [align="left|middle|right - выравнивание кнопки, по умолчанию - left"]
//      [width="Ширина - по умолчанию 100%"]) |
//    (elem_type="detail"
//      code="Код вкладки (вкладка должна быть описана в DETAIL)"
//      [height="Высота"]) |
//    (elem_type="matrix"
//      code="Код вкладки (вкладка должна быть описана в DETAIL)") |
//    (
//      (elem_type="email|url|password" 
//        datatype="string" 
//        row_type="common") | 
//      (elem_type="phone" 
//        datatype="string" 
//        [row_type="common"]
//        [db_field_addl="Поле для добавочного номера" 
//          [mandatory_addl="yes|no"]]]) | 
//      (elem_type="textarea" 
//        datatype="string" 
//        row_type="common"
//        [textarea_rows="Количество строк в многострочном поле"]
//        [is_rich_control="yes"
//          [toolbar_type="Mini"]]) | 
//      (elem_type="text" 
//        ((datatype="string|int|decimal" row_type="common") | 
//        (datatype="date|datetime" row_type="date") | 
//        (datatype="file" row_type="file"))) | 
//      (elem_type="lookup" 
//        datatype="id" 
//        row_type="fk_column"
//        row_type_parent_source_type="grid|dict"
//        row_type_parent_source_name="Код источника данных (таблица или справочник)"
//        row_type_parent_display_column="Колонка для отображения"
//        filter_where="условие фильтрации") |
//      (elem_type="select" 
//        (row_type="fk_column"
//          datatype="id"
//          (row_type_parent_table="Название таблицы"
//            row_type_parent_display_column="Колонка для отображения" 
//            [order_by="Название поля для сортировки"]
//            [db_field_ext="Дополнительные поля"]) |
//          row_type_sql="Условие для выбора значений в выпадающий список.") | 
//        (row_type="domain"
//          datatype="string|int|decimal|date|datetime|id"
//          row_type_domain_name="Код домена")) |
//      (elem_type="checkbox" 
//        row_type="domain"
//        row_type_domain_name="Название домена"
//        row_type_checked_index="Целое число, порядок значения в xml-описании домена, соответствующее отмеченному чекбоксу"
//        datatype="int|string") |
//      (elem_type="radiobutton" 
//        row_type="domain"
//        row_type_domain_name="Название домена"
//        datatype="int|string")
//      caption="Заголовок поля"
//      db_field="Название поля в БД"
//      [mandatory="yes|no"]
//      [title="Всплывющая подсказка при наведении мыши на название поля"]
//    )
//  )
    function SaveSectionCardField(&$xml, $id, $p_con = null)
    {
        $con = GetConnection($p_con);
        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, tt.type as configtype, ' .
            't1.controltype as controltype, t1.name as name, t1.issmall as issmall, ' .
            't1.datatype as datatype, t1.fieldtype as fieldtype, ' .
            't1.fieldext as fieldext, t1.ismandatoryext as ismandatoryext, ' .
            't1.rowcount as rowcount, t1.iswysiwyg as iswysiwyg, t1.toolbartype as toolbartype, ' .
            't2.code as column, t1.columnsql as columnsql, t1.ismandatory as ismandatory, t1.title as title, ' .
            't4.code as sectioncode, t3.dictionary as dictcode, t5.code as lookupcolumn, t1.dictfiltersql as lookupfiltersql, ' .
            't3.code as dicttable, t6.code as listordercolumn, t1.listextfields as listextfields, ' .
            't1.domain as domain, t1.checkedindex as checkedindex, ' .
            't1.code as code, t1.height as height, ' .
            't1.align as align, t1.width as width, t1.method as method, t1.onclick as onclick ' .
            'from iris_config_card_field t1 ' .
            'left join iris_table_column t2 on t2.id=t1.columnid ' .
            'left join iris_table t3 on t3.id=t1.dicttableid ' .
            'left join iris_section t4 on t4.id=t3.sectionid ' .
            'left join iris_table_column t5 on t5.id=t1.dictcolumnid ' .
            'left join iris_table_column t6 on t6.id=t1.listsortcolumnid ' .
            'left join iris_config_card tt1 on tt1.id=t1.configcardid ' .
            'left join iris_config tt on tt.id=tt1.configid ' .
            'where t1.configcardid = :id ' .
            'order by t1.rownumber, t1.colnumber');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);


        //Если в базе нет инфо о поле, то сразу выход
        if (count($res) == 0) {
            return null;
        }

        //Заносим значения в XML
        $config_type_code = $this->GetConfigDomainValue('d_config_file_type_', $res[0]['configtype'], true);
        $tag = null;
        if ($config_type_code == 'sections') {
            $tag = $xml->TAB->EDITCARD->addChild('ELEMENTS');
        }
        if ($config_type_code == 'dictionary') {
            $tag = $xml->DICTONARY->EDITCARD->addChild('ELEMENTS');
        }
        if ($config_type_code == 'common') {
            $tag = $xml->DETAIL->EDITCARD->addChild('ELEMENTS');
        }
        if ($config_type_code == 'tab') {
            $tag = $xml->EDITCARD->addChild('ELEMENTS');
        }
        if ($tag == null) {
            return null;
        }

        foreach ($res as $res_row) {
            $tag_field = $tag->addChild('FIELD');
            $attributes = [];

            $controltype = $this->GetConfigDomainValue('d_config_controltype', $res_row['controltype'], true);
            $datatype = $this->GetConfigDomainValue('d_config_datatype', $res_row['datatype'], true);
            $fieldtype = $this->GetConfigDomainValue('d_config_fieldtype', $res_row['fieldtype'], true);

            $this->addNotNullAttributes($attributes, [
                ['elem_type', $controltype],
            ]);

            if ($controltype == 'splitter' || $controltype == 'email' || $controltype == 'url'
                || $controltype == 'password' || $controltype == 'phone' || $controltype == 'textarea'
                || $controltype == 'text' || $controltype == 'lookup' || $controltype == 'select'
                || $controltype == 'checkbox' || $controltype == 'radiobutton' || $controltype == 'button'
            ) {
                $this->addNotNullAttributes($attributes, [
                    ['caption', $res_row['name']],
                ]);
            }

            if ($controltype == 'splitter') {
                $this->addNotNullAttributes($attributes, [
                    ['small', $this->GetYesNoNumber($res_row['issmall'], null, false, true)],
                ]);
            }

            if ($controltype == 'email' || $controltype == 'url'
                || $controltype == 'password' || $controltype == 'phone' || $controltype == 'textarea'
                || $controltype == 'text' || $controltype == 'lookup' || $controltype == 'select'
                || $controltype == 'checkbox' || $controltype == 'radiobutton'
            ) {
                $this->addNotNullAttributes($attributes, [
                    ['db_field', $res_row['columnsql'] ? $res_row['columnsql'] : $res_row['column']],
                    ['datatype', $datatype],
                    ['row_type', $fieldtype],
                    ['mandatory', $this->GetYesNoNumber($res_row['ismandatory'], null, false, true)],
                    ['title', $res_row['title']],
                ]);
            }

            if ($controltype == 'phone') {
                $this->addNotNullAttributes($attributes, [
                    ['db_field_addl', $res_row['fieldext']],
                ]);
                if ($res_row['fieldext']) {
                    $this->addNotNullAttributes($attributes, [
                        ['mandatory_addl', $this->GetYesNoNumber($res_row['ismandatoryext'], null, false, true)],
                    ]);
                }
            }

            if ($controltype == 'textarea') {
                $this->addNotNullAttributes($attributes, [
                    ['textarea_rows', $res_row['rowcount']],
                    ['is_rich_control', $this->GetYesNoNumber($res_row['iswysiwyg'], null, false, true)],
                ]);
                if ($res_row['iswysiwyg'] == 1) {
                    $this->addNotNullAttributes($attributes, [
                        ['toolbar_type', $this->GetConfigDomainValue('d_config_field_toolbar_type', $res_row['toolbartype'], true)],
                    ]);
                }
            }

            if ($controltype == 'lookup') {
                if ($res_row['dictcode']) {
                    $this->addNotNullAttributes($attributes, [
                        ['row_type_parent_source_type', 'dict'],
                        ['row_type_parent_source_name', $res_row['dictcode']],
                    ]);
                }
                else {
                    $this->addNotNullAttributes($attributes, [
                        ['row_type_parent_source_type', 'grid'],
                        ['row_type_parent_source_name', $res_row['sectioncode']],
                    ]);
                }
                $this->addNotNullAttributes($attributes, [
                    ['row_type_parent_display_column', $res_row['lookupcolumn']],
                    ['filter_where', $res_row['lookupfiltersql']],
                ]);
            }

            if ($controltype == 'select') {
                if ($fieldtype == 'domain') {
                    $this->addNotNullAttributes($attributes, [
                        ['row_type_domain_name', $res_row['domain']],
                    ]);
                }
                else {
                    if ($res_row['dicttable']) {
                        $this->addNotNullAttributes($attributes, [
                            ['row_type_parent_table', $res_row['dicttable']],
                            ['row_type_parent_display_column', $res_row['lookupcolumn']],
                            ['order_by', $res_row['listordercolumn']],
                            ['db_field_ext', $res_row['listextfields']],
                        ]);
                    }
                }
            }

            if ($controltype == 'checkbox') {
                $this->addNotNullAttributes($attributes, [
                    ['row_type_domain_name', $res_row['domain']],
                    ['row_type_checked_index', $res_row['checkedindex']],
                ]);
            }

            if ($controltype == 'radiobutton') {
                $this->addNotNullAttributes($attributes, [
                    ['row_type_domain_name', $res_row['domain']],
                ]);
            }

            if ($controltype == 'button' || $controltype == 'detail' || $controltype == 'matrix') {
                $this->addNotNullAttributes($attributes, [
                    ['code', $res_row['code']],
                ]);
            }

            if ($controltype == 'button') {
                $this->addNotNullAttributes($attributes, [
                    ['align', $this->GetConfigDomainValue('d_config_field_align', $res_row['align'], true)],
                    ['onclick', $res_row['onclick']],
                    ['method', $res_row['method']],
                    ['width', $res_row['width']],
                ]);
            }

            if ($controltype == 'detail') {
                $this->addNotNullAttributes($attributes, [
                    ['height', $res_row['height']],
                ]);
            }

            $this->addAttributes($tag_field, $attributes, true);
        }
    }


//   ######      ######  ########  ######  ########    ########    ###    ########  
//  ##    ##    ##    ## ##       ##    ##    ##          ##      ## ##   ##     ## 
//  ##          ##       ##       ##          ##          ##     ##   ##  ##     ## 
//   ######      ######  ######   ##          ##          ##    ##     ## ########  
//        ##          ## ##       ##          ##          ##    ######### ##     ## 
//  ##    ##    ##    ## ##       ##    ##    ##          ##    ##     ## ##     ## 
//   ######      ######  ########  ######     ##          ##    ##     ## ########  


//Сохранение в XML информации о вкладках раздела
//<DETAIL
//  caption="Заголовок вкладки"
//  name="Код вкладки" 
//  detail_fk_column="Колонка-связка с родительской таблицей" 
//  [detail_bound_clause="Условие связки вкладки с родительской записью"]
//  [showoncard="yes|no"]
//  [showinsection="yes|no"] 
//  (external="yes" 
//    detail_file="Файл с описанием вкладки") |
//    detail_table="Таблица вкладки">
    function SaveSectionTab(&$xml, $id, $p_con = null)
    {
        $con = GetConnection($p_con);
        //Читаем значения для записи в XML
        $cmd = $con->prepare('select t1.id as id, tt.type as configtype, ' .
            't1.name as name, t1.code as code, ' .
            't2.code as parentcode, t1.parentcolumnsql as parentcolumnsql, t1.parentsql as parentsql, ' .
            't1.displayincard as displayincard, t1.displayinsection as displayinsection, ' .
            't1.filename as filename, t3.code as tablecode, t1.tablesql as tablesql, ' .
            'tt.showaccessdetail as showaccessdetail ' .
            'from iris_config t1 ' .
            'left join iris_table_column t2 on t2.id=t1.parentid ' .
            'left join iris_table t3 on t3.id=t1.tableid ' .
            'left join iris_config tt on tt.id=t1.configid ' .
            'where t1.configid = :id ' .
            'order by t1.orderpos');
        $cmd->execute([
            ':id' => $id,
        ]);
        $res = $cmd->fetchAll(PDO::FETCH_ASSOC);


        //Если в базе нет инфо о вкладке, то сразу выход
        if (count($res) == 0) {
            return null;
        }

        //Заносим значения в XML
        $config_type_code = $this->GetConfigDomainValue('d_config_file_type_', $res[0]['configtype'], true);
        $tag = null;
        if ($config_type_code == 'sections') {
            $tag = $xml->TAB->addChild('DETAILS');
        }
        if ($config_type_code == 'dictionary') {
            $tag = $xml->DICTONARY->addChild('DETAILS');
        }
        if ($tag == null) {
            return null;
        }

        $hide_access_detail = $this->GetYesNoNumber($res[0]['showaccessdetail'], null, true, true);
        if ($hide_access_detail) {
            $attributes_tab = [];
            $this->addNotNullAttributes($attributes_tab, [
                ['hide_access_detail', $hide_access_detail],
            ]);
            $this->addAttributes($tag, $attributes_tab, true);
        }

        foreach ($res as $res_row) {
            $tag_tab = $tag->addChild('DETAIL');
            $attributes = [];

            $this->addNotNullAttributes($attributes, [
                ['caption', $res_row['name']],
                ['name', $res_row['code']],
                ['detail_fk_column', $res_row['parentcolumnsql'] ? $res_row['parentcolumnsql'] : $res_row['parentcode']],
                ['detail_bound_clause', $res_row['parentsql']],
                ['showoncard', $res_row['displayincard']],
                ['showinsection', $res_row['displayinsection']],
            ]);

            if ($res_row['filename']) {
                $this->addNotNullAttributes($attributes, [
                    ['external', 'yes'],
                    ['detail_file', $res_row['filename']],
                ]);
            }
            else {
                $this->addNotNullAttributes($attributes, [
                    ['detail_table', $res_row['tablecode'] . $res_row['tablesql']],
                ]);
            }

            $this->addAttributes($tag_tab, $attributes, true);

            //Если не внешняя вкладка, то сохраним и её описание
            if (!$res_row['filename']) {

                $tab_table_id = $this->SaveSectionTable($tag_tab, $res_row['id'], 1, $con);
                if ($tab_table_id) {
                    $this->SaveSectionTableColumn($tag_tab, $tab_table_id, 1, $con);
                }

                $tab_card_id = $this->SaveSectionCard($tag_tab, $res_row['id'], $con);
                if ($tab_card_id) {
                    $this->SaveSectionCardTab($tag_tab, $tab_card_id, $con);
                    $this->SaveSectionCardField($tag_tab, $tab_card_id, $con);
                }
            }
        }
    }

}