<?php

namespace Iris\Config\CRM\sections\Invoice;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки счёта
 */
class s_Invoice extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
        $this->_section_name = substr(__CLASS__, 2);
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
                array ('Dict' => 'InvoiceType', 'Code' => 'In'),
                array ('Dict' => 'InvoiceState', 'Code' => 'Plan'),
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
        $Number = GenerateNewNumber('InvoiceNumber', 'InvoiceNumberDate', $con);
        $result = FieldValueFormat('Number', $Number, null, $result);
        $result = FieldValueFormat('Name', $Number, null, $result);

        // Дата
        $Date = GetCurrentDBDate($con);
        $result = FieldValueFormat('Date', $Date, null, $result);

        //$Tax = GetSystemVariableValue('Tax', $con);
        //$result = FieldValueFormat('Tax', $Tax, null, $result);

        $result = GetValuesFromTable('Account_Property', $row['id'], 
                array('Tax'), $con, $result);

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        // Если создаём запись
        if (!$old_data) {
            UpdateNumber('Invoice', $id, 'InvoiceNumber', 'InvoiceNumberDate');
        }

        // Добавим клиента в доступ на чтение, если это необходимо
        $isclientaccess = GetSystemVariableValue('InvoiceClientAccess', 
                $this->connection);
        if ($isclientaccess != 1) {
            return;
        }    
        $contact_id = GetArrayValueByName($new_data['FieldValues'], 'contactid');
        if ($contact_id != null) {
            $permissions[] = array(
                'userid' => $contact_id, 
                'roleid' => '', 
                'r' => 1, 
                'w' => 0, 
                'd' => 0, 
                'a' => 0
            );
            $res = ChangeRecordPermissions('iris_invoice', $id, $permissions);
        }
    }

}
