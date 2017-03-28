<?php

namespace Iris\Config\CRM\sections\Space;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки площадки
 */
class s_Space extends Config
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
        }

        // Значения справочников
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'SpaceState', 'Code' => 'Plan')
            ), $this->connection);

        // Ответственный    
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);

        // Дата
        $Date = GetCurrentDBDate($this->connection);
        $result = FieldValueFormat('PlanStartDate', $Date, null, $result);
        $result = FieldValueFormat('StartDate', $Date, null, $result);

        $Tax = GetSystemVariableValue('Tax', $this->connection);
        $result = FieldValueFormat('Tax', $Tax, null, $result);

        return $result;
    }
}
