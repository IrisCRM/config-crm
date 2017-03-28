<?php

namespace Iris\Config\CRM\sections\Invoice;

use Config;
use Iris\Iris;

/**
 * Карточка счета
 */
class c_Invoice extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ));
    }

    public function onChangeAccountID($params, $result = null)
    {
        $select_sql = "select ap.ID, ap.Name "
                . "from iris_Account_Property ap, iris_Account a "
                . "where ap.AccountID = a.ID and ap.IsMain = 1 and a.ID = :p_id";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(':p_id' => $params['value']));
        $statement->bindColumn(1, $AccountPropertyID);
        $statement->bindColumn(2, $AccountPropertyName);
        $res = $statement->fetch();
        return FieldValueFormat('Account_PropertyID', $AccountPropertyID, 
                $AccountPropertyName, $result);
    }

    public function onChangeContactID($params)
    {
        $result = GetLinkedValues('Contact', $params['value'], 
                array('Account'), $this->connection);
        $value['value'] = $this->fieldValue($result, 'AccountID');
        return $this->onChangeAccountID($value, $result);
    }

    public function onChangeOfferID($params)
    {
        $result = GetLinkedValues('Offer', $params['value'], 
                array('Account', 'Contact', 'Project'), $this->connection);
        $value['value'] = $this->fieldValue($result, 'AccountID');
        return $this->onChangeAccountID($value, $result);
    }

    public function onChangeProjectID($params)
    {
        $result = GetLinkedValues('Project', $params['value'], 
                array('Account', 'Contact'), $this->connection);
        $value['value'] = $this->fieldValue($result, 'AccountID');
        return $this->onChangeAccountID($value, $result);
    }

    public function onChangePactID($params)
    {
        $result = GetLinkedValues('Pact', $params['value'], 
                array('Account', 'Contact', 'Project'), $this->connection);
        $value['value'] = $this->fieldValue($result, 'AccountID');
        return $this->onChangeAccountID($value, $result);
    }

    public function onChangeInvoiceStateID($params)
    {
        $StateCode = GetFieldValueByID('InvoiceState', $params['value'], 
                'Code', $this->connection);
        if ($StateCode == 'Payed') {
            $date = GetCurrentDBDate($this->connection);
            $result = FieldValueFormat('PaymentDate', $date, null, $result);

            $enabled = $this->getFieldProperties($params);
            if ($enabled['EnabledFields']['PaymentAmount']) {
                $amount = GetFieldValueByID('Invoice', $params['id'], 'Amount', 
                        $this->connection);
                $result = FieldValueFormat('PaymentAmount', $amount, null, 
                        $result);
            }
        }
        return $result;
    }

    public function onChangePaymentAmount($params)
    {
        $amount = GetFieldValueByID('Invoice', $params['id'], 'Amount', 
                $this->connection);
        if ($params['value'] >= $amount) {
            $result = GetDictionaryValues(
                array (
                    array ('Dict' => 'InvoiceState', 'Code' => 'Payed')
                ), $this->connection, $result);
        }
        else {
            $result = GetDictionaryValues(
                array (
                    array ('Dict' => 'InvoiceState', 'Code' => 'Part')
                ), $this->connection, $result);
        }
        $date = GetCurrentDBDate($this->connection);
        return FieldValueFormat('PaymentDate', $date, null, $result);
    }

    public function getFieldProperties($params)
    {
        $select_sql = "select count(*) from iris_Invoice_Product "
                . "where InvoiceID = :p_RecordID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_RecordID' => $params['id'],
        ));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();

        $result['EnabledFields']['Amount'] = $Number == 0;

        // Получим код типа счета (для использования в следующем блоке)
        $select_sql = "select it.Code from iris_Invoice i ";
        $select_sql .= "left join iris_InvoiceType it on it.ID = i.InvoiceTypeID ";
        $select_sql .= "where i.ID = :p_RecordID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_RecordID' => $params['id'],
        ));
        $statement->bindColumn(1, $Code);
        $res = $statement->fetch();

        // Сумма оплаты. Если есть платежи, то блокировать.
        $select_sql = "select count(*) from iris_Payment p ";
        $select_sql .= "left join iris_PaymentType pt on pt.ID = p.PaymentTypeID ";
        $select_sql .= "where p.InvoiceID = :p_RecordID and pt.Code = :p_Code";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_RecordID' => $params['id'],
            ':p_Code' => $Code,
        ));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        $PaymentAmount_enabled = $Number == 0 ? true : false;
        $result['EnabledFields']['PaymentAmount'] = $PaymentAmount_enabled;


        $UserName = GetUserName();

        // Получить реквизиты по умолчанию Вашей компании
        $select_sql = "select a.ID "
                . "from iris_Account_Property ap, iris_Account a, iris_Contact c "
                . "where ap.AccountID = a.ID and a.ID = c.AccountID " 
                . "and c.Login = :p_UserName";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_UserName' => $UserName,
        ));
        $statement->bindColumn(1, $AccountID);
        $res = $statement->fetch();

        $result['FilterFields']['AccountID'] = $AccountID;

        return $result;
    }
}
