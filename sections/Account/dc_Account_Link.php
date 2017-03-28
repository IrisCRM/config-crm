<?php

namespace Iris\Config\CRM\sections\Account;

use Config;
use Iris\Iris;

/**
 * Раздел "Компании". Закладка "Связи". Карточка.
 */

class dc_Account_Link extends Config
{

    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    function checkReverseLink($parameters)
    {
        $RecordID = null;
        if (!empty($parameters['id'])) {
            $RecordID = $parameters['id'];
        }
        else {
            $Action = 'Error';
            $l_result['AccountLink']['Action'] = $Action;
        }
        $con = db_connect();

        //Параметры существующих связей
        $select_sql = "select al1.AccountID, al1.AccountLinkID, " 
                . "al1.AccountLinkRoleID, al1.Description, " 
                . "alr.ReverseLinkRoleID, al2.ID "
                . "from iris_Account_Link al1 "
                . "left join iris_AccountLinkRole alr " 
                    . "on alr.ID = al1.AccountLinkRoleID "
                . "left join iris_Account_Link al2 " 
                    . "on al2.AccountID = al1.AccountLinkID " 
                    . "and al2.AccountLinkID = al1.AccountID " 
                    . "and al2.AccountLinkRoleID = alr.ReverseLinkRoleID "
                . "where al1.ID = :p_id";
        $statement = $con->prepare($select_sql);
        $statement->bindParam(':p_id', $RecordID);
        $statement->execute();
        $statement->bindColumn(1, $Account1ID);
        $statement->bindColumn(2, $Account2ID);
        $statement->bindColumn(3, $Link1ID);
        $statement->bindColumn(4, $Description);
        $statement->bindColumn(5, $Link2ID);
        $statement->bindColumn(6, $ID2);
        $res = $statement->fetch();
        
        $Action = 'None';
        
        //если должна быть задана обратная связь, а ее нет, то добавим
        if ($Link2ID != '' && $ID2 == '') {
            $id = create_guid();
            $insert_sql = "insert into iris_Account_Link (id, AccountID, " 
                    . "AccountLinkID, AccountLinkRoleID, Description) "
                    . "values (:p_id, :p_account_id, :p_accountlink_id, " 
                    . ":p_accountlinkrole_id, :p_description)";
            $statement = $con->prepare($insert_sql);
            $statement->bindParam(':p_id', $id);
            $statement->bindParam(':p_account_id', $Account2ID);
            $statement->bindParam(':p_accountlink_id', $Account1ID);
            $statement->bindParam(':p_accountlinkrole_id', $Link2ID);
            $statement->bindParam(':p_description', $Description);
            $statement->execute();
            $Action = 'Insert';
        }
        
        $l_result['AccountLink']['Action'] = $Action;
        return $l_result;
    }
}