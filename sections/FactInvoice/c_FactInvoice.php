<?php

namespace Iris\Config\CRM\sections\FactInvoice;

use Config;
use Iris\Iris;

/**
 * Карточка накладной
 */
class c_FactInvoice extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
        ));
    }

    public function onChangeContactID($params)
    {
        return GetLinkedValues('Contact', $params['value'], 
                array('Account'), $this->connection);
    }

    public function onChangeProjectID($params)
    {
        return GetLinkedValues('Project', $params['value'], 
                array('Account', 'Contact'), $this->connection);
    }

    public function onChangePactID($params)
    {
        return GetLinkedValues('Pact', $params['value'], 
                array('Account', 'Contact', 'Project'), $this->connection);
    }

    public function onChangeInvoiceID($params)
    {
        return GetLinkedValues('Invoice', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection);
    }

    public function onChangeFactInvoiceStateID($params)
    {
        $StateCode = GetFieldValueByID('FactInvoiceState', $params['value'], 
                'Code', $this->connection);
        if ($StateCode == 'Sended') {
            $date = GetCurrentDBDate($this->connection);
            $result = FieldValueFormat('Date', $date, null, $result);
        }
        return $result;
    }

    public function getFieldProperties($params)
    {
        $select_sql = "select count(*) as number " 
                . "from iris_FactInvoice_Product "
                . "where FactInvoiceID = :p_RecordID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_RecordID' => $params['id'],
        ));
        $row = $statement->fetch();

        $result['EnabledFields']['Amount'] = $row['number'] == 0;
        return $result;
    }
}
