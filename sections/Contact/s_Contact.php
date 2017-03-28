<?php

namespace Iris\Config\CRM\sections\Contact;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки контакта
 */
class s_Contact extends Config
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

        $con = $this->connection;

        $result = $this->prepareDetail($params);

        // Значения справочников
        $result = GetDictionaryValues(
            array(
                array ('Dict' => 'ContactType', 'Code' => 'Client')
            ), $con, $result
        );

        // Ответственный
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $con, $result);


        // Получим страну, город и область из компании пользователя
        $UserID = GetArrayValueByParameter($result['FieldValues'], 'Name', 'OwnerID', 'Value');
        $AccountID = GetFieldValueByID('Contact', $UserID, 'AccountID', $con);
        $result = GetLinkedValues('Account', $AccountID, array('Country', 'City', 'Region'), $con, $result);

        $result = FieldValueFormat('ispersonalinfoagree', true, null, $result);
        $result = FieldValueFormat('isnotify', 1, null, $result);
        $result = FieldValueFormat('balance', 0, null, $result);

        return $result;
    }

    // Перед сохранением карточки
    function onBeforePost($parameters) {
        $role_id = GetArrayValueByName($parameters['new_data']['FieldValues'], 
                'AccessRoleID');
        if ($role_id != null)
            return array('Error' => 'Смена роли невозможна');
        return $parameters['new_data'];
    }

    public function onBeforePostRegionID($parameters)
    {
        $value = $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], 'RegionID');
        if (!$value) {
            return null;
        }
        return GetLinkedValues('Region', $value, array('Country'), 
                $this->connection);
    }

    public function onBeforePostCityID($parameters)
    {
        $value = $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], 'CityID');
        if (!$value) {
            return null;
        }
        return GetLinkedValues('City', $value, array('Country', 'Region'), 
                $this->connection);
    }

    // После сохранения карточки
    public function onAfterPost($p_table, $p_id, $OldData, $NewData) {
        $account_id = GetArrayValueByName($NewData['FieldValues'], 'accountid');
        if ($account_id != null) {
            $con = db_connect();
            // если у этой компани есть только 1 контакт, то сделаем его основным
            $sql = "update iris_account T0 " 
                    . "set primarycontactid=:contactid "
                    . "where id=:accountid and " 
                    . "(select count(id) from iris_contact where accountid=T0.id) = 1";
            $cmd = $con->prepare($sql);
            $cmd->execute(array(":contactid" => $p_id, ":accountid" => $account_id));
        }
    }

    function onBeforePostAccountID($parameters)
    {
        $value = $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], 'AccountID');

        $result = GetFormatedFieldValuesByFieldValue('Account', 'ID', 
                $value, array('Address', 'ZIP', 'Scheme'), $this->connection);
        $result = GetLinkedValues('Account', $value, 
                array('Country', 'Region', 'City'), $this->connection, $result);
        return $result;
    }

    function onBeforePostObjectID($parameters)
    {
        $value = $this->getActualValue($parameters['old_data'], 
                $parameters['new_data'], 'ObjectID');

        $result = GetFormatedFieldValuesByFieldValue('Object', 'ID', 
                $value, array('Address', 'ZIP', 'Scheme'), $this->connection);
        $result = GetLinkedValues('Object', $value, 
                array('Country', 'Region', 'City', 'Account'), $this->connection, $result);
        return $result;
    }
}
