<?php

namespace Iris\Config\CRM\sections\Issue;

use Config;
use Iris\Iris;

/**
 * Карточка проекта
 */
class s_Issue extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php'));
    }

    public function onPrepare($params) 
    {
        $result = GetDictionaryValues(
            array (
                array ('Dict' => 'IssueState', 'Code' => 'Plan')
            ), $this->connection);

        //Ответственный    
        $result = GetDefaultOwner(GetUserName(), $this->connection, $result);

        //Даты
        $Date = GetCurrentDBDate($this->connection);
        $result = FieldValueFormat('StartDate', $Date, null, $result);
        $result = FieldValueFormat('PlanStartDate', $Date, null, $result);
        
        return $result;
    }

}
