<?php

namespace Iris\Config\CRM\sections\Offer;

use Config;
use Iris\Iris;

/**
 * Карточка КП
 */
class c_Offer extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'common/Lib/lib.php'));
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

    public function getEnabledFields($params)
    {
        $select_sql = "select count(*) from iris_Offer_Product "
        		. "where OfferID = :p_RecordID";
        $statement = $this->connection->prepare($select_sql);
        $statement->bindParam(':p_RecordID', $params['id']);
        $statement->execute();
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();

        $result['EnabledFields']['Amount'] = $Number == 0;
        return $result;
    }
}
