<?php
/**
 * Серверная логика вкдалки полей отчёта
 */

namespace Iris\Config\CRM\sections\Report;

use Iris\Config\CRM\common\Lib\ReportConfig;
use Iris\Iris;

class ds_Report_Parameter extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, [
            'common/Lib/lib.php']);
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }
        $result = null;

        //Номер
        $result = FieldValueFormat('Number', 
                $this->getPosition($params['detail_column_value'], 'Parameter'),
                null, $result);

        //Показывать
        $result = FieldValueFormat('IsVisible', 1, null, $result);

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data) {
        // Перенумеруем позиции при необходимости
        $this->renumber($old_data, $new_data, $id, 'Parameter');
    }

}
