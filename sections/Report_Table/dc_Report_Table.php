<?php
/**
 * Карточка таблицы отчёта
 */

namespace Iris\Config\CRM\sections\Report_Table;

use Iris\Config\CRM\common\Lib\ReportConfig;

class dc_Report_Table extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader);
    }

    public function onChangeTableID($params)
    {
        return GetValuesFromTable('Table', $params['value'], 
                array('Name', 'Code'), $this->connection);
    }

}
