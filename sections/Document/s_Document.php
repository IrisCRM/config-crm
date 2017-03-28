<?php

namespace Iris\Config\CRM\sections\Document;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки документа
 */
class s_Document extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
        ));
    }

    public function onPrepare($params, $result = null) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $con = $this->connection;

        $this->mergeFields($result, $this->prepareDetail($params), false);

        // Значения справочников
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'Currency', 'Code' => 'RUB')
            ), $con, $result);

        // Ответственный    
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $con, $result);

        // Получить реквизиты по умолчанию Вашей компании
        $select_sql = "select ap.ID as id, ap.Name as name "
                . "from iris_Account_Property ap, iris_Account a, iris_Contact c "
                . "where ap.AccountID = a.ID and a.ID = c.AccountID " 
                . "and c.Login = :p_UserName and ap.IsMain = 1";
        $statement = $con->prepare($select_sql);
        $statement->execute(array(
            ':p_UserName' => $UserName,
        ));
        $row = $statement->fetch();
        $result = FieldValueFormat('Your_PropertyID', 
                $row['id'], $row['name'], $result);

        // Номер
        $Number = GenerateNewNumber('DocumentNumber', 'DocumentNumberDate', $con);
        $result = FieldValueFormat('Number', $Number, null, $result);
        $result = FieldValueFormat('Name', $Number, null, $result);

        // Дата
        $Date = GetCurrentDBDate($con);
        $result = FieldValueFormat('Date', $Date, null, $result);

        $result = GetValuesFromTable('Account_Property', $row['id'], 
                array('Tax'), $con, $result);

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        // Если создаём запись
        if (!$old_data) {
            UpdateNumber('Document', $id, 'DocumentNumber', 'DocumentNumberDate');
        }
    }

}
