<?php
/**
 * Карточка фильтра отчёта
 */

namespace Iris\Config\CRM\sections\Report;

use Iris\Config\CRM\common\Lib\ReportConfig;
use PDO;

class dc_Report_Parameter extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader);
    }

    public function onChangeTypeID($params)
    {
        $result = FieldValueFormat('TableID', null);

        //Название типа колонки
        $typecode = GetFieldValueByID('ColumnType', $params['value'], 
                'SystemCode', $this->connection);
        $result['ColumnInfo']['Type'] = $typecode;
        if ('guid' == $typecode) {
            $result['ColumnInfo']['SourceTable'] = '';
            $result['ColumnInfo']['SourceType'] = '';
            $result['ColumnInfo']['SourceName'] = '';
        }

        return $result;
    }

    public function onChangeTableID($params)
    {
        $typecode = 'guid';
        $result['ColumnInfo']['Type'] = $typecode;            
        $res = $this->_getTableParams($params['value']);
        if ('guid' == $typecode) {
            $result['ColumnInfo']['SourceTable'] = $res['table_code'];
            $result['ColumnInfo']['SourceType'] = $res['source_type_value'];
            $result['ColumnInfo']['SourceName'] = $res['source_name_value'];
        }

        return $result;
    }

    /**
     * Получить значение параметра
     */
    public function getParameterValue($params)
    {
        $fieldtype = $params['type'];
        if ('datetime' == $fieldtype) {
            $fieldtype = 'date';
        }

        $fieldname = ucfirst($fieldtype).'Value';
        list($fieldvalue, $tableid) = GetFieldValuesByID('Report_Parameter', 
                $params['id'], array($fieldname, 'TableID'), $this->connection);
        $fieldcaption = '';
        if ('guid' == $fieldtype) {
            $res = $this->_getTableParams($tableid);
            $fieldcaption = GetFieldValueByID(substr($res['table_code'], 5), 
                    $fieldvalue, 'Name', $this->connection);
            $result['ColumnInfo'] = array(
                'SourceType' => $res['source_type_value'],
                'SourceName' => $res['source_name_value']
            );
        }

        $result['ColumnInfo']['Value'] = array(
            'Name' => $fieldname,
            'Value' => $fieldvalue,
            'Caption' => json_encode_str($fieldcaption)
        );

        $result['ColumnInfo']['Type'] = $params['type'];
        return $result;
    }

    protected function _getTableParams($table_id)
    {
        $sql  = "select T0.code as table_code, "
                . "case when T1.code is not null then 'grid' else 'dict' end as source_type_value, "
                . "case when T1.code is not null then T1.code else T0.dictionary end as source_name_value "
                . "from iris_table T0 left join iris_section T1 on T0.sectionid = T1.id "
                . "where T0.id = :tableid";
        $cmd = $this->connection->prepare($sql);
        $cmd->execute(array(":tableid" => $table_id));
        return current($cmd->fetchAll(PDO::FETCH_ASSOC));
    }
}
