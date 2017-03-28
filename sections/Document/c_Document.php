<?php

namespace Iris\Config\CRM\sections\Document;

use Config;
use Iris\Iris;

/**
 * Карточка документа
 */
class c_Document extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php',
        ));
    }

    public function onChangeAccountID($params, $result = null)
    {
        $select_sql = "select ap.ID as id, ap.Name as name "
                . "from iris_Account_Property ap, iris_Account a "
                . "where ap.AccountID = a.ID and ap.IsMain = 1 and a.ID = :p_id";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_id' => $params['value'],
        ));
        $row = $statement->fetch();
        return FieldValueFormat('Account_PropertyID', $row['id'], $row['name'],
                $result);
    }

    public function onChangeContactID($params)
    {
        $result = GetLinkedValues('Contact', $params['value'], 
                array('Account'), $this->connection);
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

    public function getFieldProperties($params)
    {
        $select_sql = "select count(*) as number from iris_Document_Product "
                . "where DocumentID = :p_RecordID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_RecordID' => $params['id'],
        ));
        $row = $statement->fetch();
        $result['EnabledFields']['Amount'] = $row['number'] == 0;

        // Получить реквизиты по умолчанию Вашей компании
        $select_sql = "select a.ID as id "
                . "from iris_Account_Property ap, iris_Account a, iris_Contact c "
                . "where ap.AccountID = a.ID and a.ID = c.AccountID " 
                . "and c.Login = :p_UserName";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_UserName' => GetUserName(),
        ));
        $row = $statement->fetch();
        $result['FilterFields']['AccountID'] = $row['id'];

        return $result;
    }
}
