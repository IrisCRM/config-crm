<?php

namespace Iris\Config\CRM\sections\Object;

use Config;
use Iris\Iris;

/**
 * Карточка объекта
 */
class c_Object extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    function onChangeAccountID($params)
    {
        $result = GetFormatedFieldValuesByFieldValue('Account', 'ID', 
                $params['value'], array('Address', 'ZIP', 'Scheme'), 
                $this->connection);
        $result = GetLinkedValues('Account', $params['value'], 
                array('Country', 'Region', 'City'), $this->connection, $result);
        return $result;
    }

    function onChangeContactID($params)
    {
        $result = GetFormatedFieldValuesByFieldValue('Contact', 'ID', 
                $params['value'], array('Address', 'ZIP', 'Scheme'), 
                $this->connection);
        $result = GetLinkedValues('Contact', $params['value'], 
                array('Country', 'Region', 'City', 'Account'), 
                $this->connection, $result);
        return $result;
    }

    function onChangeRegionID($params)
    {
        return GetLinkedValues('Region', $params['value'], 
                array('Country'), $this->connection);
    }

    function onChangeCityID($params)
    {
        return GetLinkedValues('City', $params['value'], 
                array('Country', 'Region'), $this->connection);
    }
}
