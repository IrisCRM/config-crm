<?php
/**
 * Серверная логика фильтра отчёта
 */

namespace Iris\Config\CRM\sections\Report_Filter;

use Iris\Config\CRM\common\Lib\ReportConfig;

class ds_Report_Filter extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader);
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = null;

        // Номер
        $result = FieldValueFormat('Number', 
                $this->getPosition($params['detail_column_value'], 'Filter'),
                null, $result);

        // Условие по умолчанию
        $result = FieldValueFormat('condition', 1, null, $result);

        // Отображать
        $result = FieldValueFormat('isvisible', 1, null, $result);
        
        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data) {
        // Перенумеруем позиции при необходимости
        $this->renumber($old_data, $new_data, $id, 'Filter');
    }

}
