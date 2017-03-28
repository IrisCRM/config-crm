<?php

namespace Iris\Config\CRM\sections\Pact;

use Config;
use Iris\Iris;

/**
 * Карточка договора
 */
class c_Pact extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php'));
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

    public function onChangeParentPactID($params)
    {
        $result = GetLinkedValues('Pact', $params['value'], 
                array('Account', 'Contact'), $this->connection);

        // Альтернативный номер (номер приложения, спецификации и т.п.)
        if ($params['value']) {
            $select_sql = "select max(t0.AltNumber) as altnumber "
                    . "from iris_Pact t0 "
                    . "where t0.ParentPactID = :parent_pact_id";
            $statement = $this->connection->prepare($select_sql);
            $statement->execute(array(':parent_pact_id' => $params['value']));
            $row = $statement->fetch();
            $result = FieldValueFormat('AltNumber', $row['altnumber'] + 1, 
                    null, $result);
        }

        $value['value'] = $this->fieldValue($result, 'AccountID');
        return $this->onChangeAccountID($value, $result);
    }

    public function getFieldProperties($params)
    {
        $select_sql = "select count(*) from iris_Offer_Product "
                . "where OfferID = :p_RecordID";
        $statement = $this->connection->prepare($select_sql);
        $statement->bindParam(':p_RecordID', $params['id']);
        $statement->execute();
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();

        $result['EnabledFields']['Amount'] = $Number == 0;


        $UserName = GetUserName();

        //Получить реквизиты по умолчанию Вашей компании
        $select_sql = "select a.ID "
                . "from iris_Account_Property ap, iris_Account a, iris_Contact c "
                . "where ap.AccountID = a.ID and a.ID = c.AccountID " 
                . "and c.Login = :p_UserName";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(':p_UserName' => $UserName));
        $statement->bindColumn(1, $AccountID);
        $res = $statement->fetch();
        
        $result['FilterFields']['AccountID'] = $AccountID;

        return $result;
    }
}
