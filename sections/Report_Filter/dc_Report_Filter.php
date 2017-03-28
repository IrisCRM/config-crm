<?php
/**
 * Карточка фильтра отчёта
 */

namespace Iris\Config\CRM\sections\Report_Filter;

use Iris\Config\CRM\common\Lib\ReportConfig;

class dc_Report_Filter extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader);
    }

    public function onChangeColumnID($params)
    {
        $result = GetValuesFromTable('Table_Column', $params['value'], 
                array('Name'), $this->connection);
        
        // Если тип колонки - GUID, то возвратим значения
        // для изменения поля справочника
        $sql = "select ct.SystemCode as typename, t.Code as tablecode, "
                . "t.is_access as isaccess "
                . "from iris_Table_Column tc "
                . "left join iris_ColumnType ct on tc.columntypeid = ct.id "
                . "left join iris_Table t on tc.fktableid = t.id "
                . "where tc.ID = :p_id";
        $statement = $this->connection->prepare($sql);
        $statement->execute(array(':p_id' => $params['value']));
        $res = $statement->fetch();
        if (!$res) {
            $result['Error'] = $sql.'/'.$params['value'];
            return;
        }
        // Название типа колонки
        $result['ColumnInfo']['Type'] = $res['typename'];
        if ('guid' == $res['typename']) {
            if ($res['tablecode']) {
                $result['ColumnInfo']['SourceTable'] = $res['tablecode'];
                if ($res['isaccess']) {
                    $result['ColumnInfo']['SourceType'] = 'grid';
                    $result['ColumnInfo']['SourceName'] = 
                            ucfirst(substr($res['tablecode'], 5));
                }
                else {
                    //TODO: сделать также проверку в xml
                    $result['ColumnInfo']['SourceType'] = 'dict';
                    $result['ColumnInfo']['SourceName'] = 
                            substr($res['tablecode'], 5);
                }
            }
        }
        return $result;
    }

    /**
     * Получить значение фильтра
     */
    public function getFilterValue($params)
    {
        $result = $this->onChangeColumnID(array('value' => $params['column_id']));

        $fieldtype = $result['ColumnInfo']['Type'];
        if ('datetime' == $fieldtype) {
            $fieldtype = 'date';
        }

        if ($fieldtype) {
            $fieldname = ucfirst($fieldtype).'Value';
            $fieldvalue = GetFieldValueByID('Report_Filter', $params['id'], 
                    $fieldname, $this->connection);
            $fieldcaption = '';
            if ('guid' == $fieldtype) {
                $fieldcaption = GetFieldValueByID(
                        substr($result['ColumnInfo']['SourceTable'], 5), 
                        $fieldvalue, 'Name', $this->connection);
            }

            $result['ColumnInfo']['Value'] = array(
                'Name' => $fieldname,
                'Value' => $fieldvalue,
                'Caption' => json_encode_str($fieldcaption),
            );
        }
        return $result;
    }
}
