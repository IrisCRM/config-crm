<?php
/**
 * Карточка поля отчёта
 */

namespace Iris\Config\CRM\sections\Report_Column;

use Iris\Config\CRM\common\Lib\ReportConfig;

class dc_Report_Column extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader);
    }

    public function onChangeColumnID($params)
    {
        return GetValuesFromTable('Table_Column', $params['value'], 
                array('Name', 'Code', 'ColumnTypeID'), $this->connection);
    }

}