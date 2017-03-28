<?php

namespace Iris\Config\CRM\sections\Issue;

use Config;
use Iris\Iris;

/**
 * Карточка проекта
 */
class c_Issue extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
             'common/Lib/lib.php'));
    }

    public function onChangeIssueStateID($params, $con = null) 
    {
        $result = null;
        $StateCode = GetFieldValueByID('IssueState', $params['value'], 'Code', 
                $this->connection);
        if ($StateCode == 'Finished') {
            $date = GetCurrentDBDate($this->connection);
            $result = FieldValueFormat('FinishDate', $date, null, $result);
        }
        return $result;
    }
}
