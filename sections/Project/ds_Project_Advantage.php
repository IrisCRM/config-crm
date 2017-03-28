<?php

namespace Iris\Config\CRM\sections\Project;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки компании
 */
class ds_Project_Advantage extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        };

        $result = null;

        // Дата
        $Date = GetCurrentDBDate($this->connection);
        $result = FieldValueFormat('AdvantageDate', $Date, null, $result);
        $result = FieldValueFormat('Count', 1, null, $result);

        return $result;
    }
}
