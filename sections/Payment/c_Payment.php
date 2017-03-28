<?php

namespace Iris\Config\CRM\sections\Payment;

use Config;
use Iris\Iris;

/**
 * Карточка платежа
 */
class c_Payment extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    public function onChangeContactID($params, $con = null)
    {
        return GetLinkedValues('Contact', $params['value'], 
                array('Account'), $con);
    }

    public function onChangeProjectID($params, $con = null)
    {
        return GetLinkedValues('Project', $params['value'], 
                array('Account', 'Contact'), $con);
    }

    public function onChangeInvoiceID($params, $con = null)
    {
        $result = GetLinkedValues('Invoice', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact'), $con);
        
        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $con, $result);
        
        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact'), $con, $result);

        return $result;
    }

    public function onChangePactID($params, $con = null)
    {
        $result = GetLinkedValues('Pact', $params['value'], 
                array('Account', 'Contact', 'Project'), $con);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact'), $con, $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ContactID', 'Value');
        $result = GetLinkedValues('Contact', $id, 
                array('Account'), $con, $result);

        return $result;
    }

    public function onChangeFactInvoiceID($params, $con = null)
    {
        $result = GetLinkedValues('FactInvoice', $params['value'], 
            array('Account', 'Contact', 'Project', 'Pact', 'Invoice'), $con);
        
        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'InvoiceID', 'Value');
        $result = GetLinkedValues('Invoice', $id, 
                array('Account', 'Contact', 'Project'), $con, $result);
        
        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $con, $result);
        
        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact'), $con, $result);

        return $result;
    }

    public function onChangePaymentStateID($params, $con = null)
    {
        $result = null;
        $StateCode = GetFieldValueByID(
                'PaymentState', $params['value'], 'Code', $con);

        if ($StateCode == 'Completed') {
            $date = GetCurrentDBDate($con);
            $result = FieldValueFormat('PaymentDate', $date, null, $result);
        }

        return $result;
    }

    public function onChangeAmount($params, $con = null)
    {
        $result = null;
        if ($params['value'] > 0) {
            $result = GetDictionaryValues(
                array (
                    array ('Dict' => 'PaymentState', 'Code' => 'Completed')
                ), $con);
            $date = GetCurrentDBDate($con);
            $result = FieldValueFormat('PaymentDate', $date, null, $result);
        }
        return $result;
    }

}
