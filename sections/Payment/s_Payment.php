<?php

namespace Iris\Config\CRM\sections\Payment;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки платежа
 */
class s_Payment extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, [
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ]);
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

        //Значения справочников
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'PaymentType', 'Code' => 'In'),
                array ('Dict' => 'PaymentState', 'Code' => 'Plan'),
                array ('Dict' => 'Currency', 'Code' => 'RUB'),
            ), $con, $result);

        //Ответственный    
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $con, $result);
        
        //Номер
        $Number = GenerateNewNumber('PaymentNumber', 'PaymentNumberDate', $con);    
        $result = FieldValueFormat('Number', $Number, null, $result);
        $result = FieldValueFormat('Name', $Number, null, $result);
        
        //Дата
        $Date = GetCurrentDBDate($con);
        $result = FieldValueFormat('PlanPaymentDate', $Date, null, $result);
        
    //    $Tax = GetSystemVariableValue('Tax', $con);
    //    $result = FieldValueFormat('Tax', $Tax, null, $result);

        //Получить реквизиты по умолчанию Вашей компании
        $select_sql = "select ap.ID, ap.Name "
                . "from iris_Account_Property ap, iris_Account a, iris_Contact c "
                . "where ap.AccountID=a.ID and a.ID=c.AccountID " 
                . "and c.Login=:p_UserName and ap.IsMain=1";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_UserName', $UserName);
        $statement->execute();
        $statement->bindColumn(1, $AccountPropertyID);
        $res = $statement->fetch();
        
        $result = GetValuesFromTable('Account_Property', $AccountPropertyID, 
                array('Tax'), $con, $result);
        
        return $result;
    }

    public function onAfterPost($p_table, $p_id, $OldData, $NewData)
    {
        $con = GetConnection();

        if (!$OldData) {
            UpdateNumber('Payment', $p_id, 'PaymentNumber', 'PaymentNumberDate');
            return;
        }

        $isclientaccess = GetSystemVariableValue('PaymentClientAccess', $con);
        if ($isclientaccess != 1) {
            return;
        }
        $contact_id_old = GetArrayValueByName($OldData['FieldValues'], 'contactid');
        $contact_id = GetArrayValueByName($NewData['FieldValues'], 'contactid');
        if ($contact_id != null && $contact_id_old != $contact_id) {
            $permissions[] = array(
                'userid' => $contact_id, 
                'roleid' => '', 
                'r' => 1, 
                'w' => 0, 
                'd' => 0, 
                'a' => 0
            );
            $res = ChangeRecordPermissions('iris_payment', $p_id, $permissions, $con);
        }
        
        if ($contact_id_old != null && $contact_id_old != $contact_id) {
            $permissions[] = array(
                'userid' => $contact_id_old, 
                'roleid' => '', 
                'r' => 0, 
                'w' => 0, 
                'd' => 0, 
                'a' => 0
            );
            $res = ChangeRecordPermissions('iris_payment', $p_id, $permissions, $con);
        }
    }

}
