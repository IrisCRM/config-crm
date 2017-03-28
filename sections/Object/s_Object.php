<?php

namespace Iris\Config\CRM\sections\Object;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки объекта
 */
class s_Object extends Config
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

        // Значения справочников
        $result = GetDictionaryValues(
            array(
                array('Dict' => 'ObjectState', 'Code' => 'Active')
            ), $this->connection);

        // Ответственный
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $this->connection, $result);

        // Получим страну, город и область из компании пользователя
        $UserID = GetArrayValueByParameter($result['FieldValues'], 
                'Name', 'OwnerID', 'Value');
        $AccountID = GetFieldValueByID('Contact', $UserID, 'AccountID', 
                $this->connection);
        $result = GetLinkedValues('Account', $AccountID, 
                array('Country', 'City', 'Region'), $this->connection, $result);

        return $result;
    }
}
