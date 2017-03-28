<?php

namespace Iris\Config\CRM\sections\Marketing;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки маркетинга
 */
class s_Marketing extends Config
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
                array ('Dict' => 'MarketingTarget', 'Code' => 'New'),
                array ('Dict' => 'MarketingState', 'Code' => 'Plan'),
                array ('Dict' => 'MarketingType', 'Code' => 'Article')
            ), $this->connection);

        // Ответственный    
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);

        // Дата
        $Date = GetCurrentDBDate($this->connection);
        $result = FieldValueFormat('PlanStartDate', $Date, null, $result);
        $result = FieldValueFormat('StartDate', $Date, null, $result);

        return $result;
    }
}
