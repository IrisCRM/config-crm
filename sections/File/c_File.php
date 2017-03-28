<?php

namespace Iris\Config\CRM\sections\File;

use Config;
use Iris\Iris;

/**
 * Серверная логика карточки файла
 */
class c_File extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
        ));
    }
/*
    public function onChangeFileID($params)
    {
        return GetLinkedValues('File', $params['value'], 
                array('Account', 'Contact', 'Object', 'Product', 'Project', 
                    'Issue', 'Bug', 'Marketing', 'Space', 'Offer', 'Pact', 
                    'Invoice', 'Payment', 'FactInvoice', 'Document', 
                    'Incident'), 
                $this->connection);
    }
*/
    public function onChangeContactID($params)
    {
        return GetLinkedValues('Contact', $params['value'], 
                array('Account', 'Object'), $this->connection);
    }

    public function onChangeObjectID($params)
    {
        return GetLinkedValues('Object', $params['value'], 
                array('Account', 'Contact'), $this->connection);
    }

    public function onChangeProjectID($params)
    {
        return GetLinkedValues('Project', $params['value'], 
                array('Account', 'Object', 'Contact'), $this->connection);
    }

    public function onChangeIssueID($params)
    {
        return GetLinkedValues('Issue', $params['value'], 
                array('Product'), $this->connection);
    }

    public function onChangeBugID($params)
    {
        $result = GetLinkedValues('Bug', $params['value'], 
                array('Project', 'Issue'), $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'IssueID', 'Value');
        $result = GetLinkedValues('Issue', $id, 
                array('Product'), $this->connection, $result);

        return $result;
    }

    public function onChangeIncidentID($params)
    {
        return GetLinkedValues('Incident', $params['value'], 
                array('Account', 'Contact', 'Object', 'Product', 'Issue', 
                    'Marketing', 'Space', 'Project', 'Offer', 'Pact', 
                    'Invoice', 'Payment', 'FactInvoice', 'Document'), 
                $this->connection);
    }

    public function onChangeOfferID($params)
    {
        $result = GetLinkedValues('Offer', $params['value'], 
                array('Project', 'Account', 'Contact'), $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangePactID($params)
    {
        $result = GetLinkedValues('Pact', $params['value'], 
                array('Account', 'Contact', 'Project'), $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangeInvoiceID($params)
    {
        $result = GetLinkedValues('Invoice', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact', 'Offer'), 
                $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangePaymentID($params)
    {
        $result = GetLinkedValues('Payment', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact', 'Invoice'), 
                $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'InvoiceID', 'Value');
        $result = GetLinkedValues('Invoice', $id, 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection, $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangeFactInvoiceID($params)
    {
        $result = GetLinkedValues('FactInvoice', $params['value'], 
            array('Account', 'Contact', 'Project', 'Pact', 'Invoice'), 
            $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'InvoiceID', 'Value');
        $result = GetLinkedValues('Invoice', $id, 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection, $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangeDocumentID($params)
    {
        $result = GetLinkedValues('Document', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangeTaskID($params)
    {
        return GetLinkedValues('Task', $params['value'], 
                array('Account', 'Contact', 'Object', 'Product', 'Project', 
                    'Issue', 'Bug', 'Marketing', 'Space', 'Offer', 'Pact', 
                    'Invoice', 'Payment', 'FactInvoice', 'Document', 
                    'Incident'), 
                $this->connection);
    }

    public function onChangeEmailID($params)
    {
        return GetLinkedValues('Email', $params['value'], 
                array('Account', 'Contact'), $this->connection);
    }

}
