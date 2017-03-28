<?php

namespace Iris\Config\CRM\sections\Contact;

use Config;
use Iris\Iris;

/**
 * Серверная логика вкладки Связи карточки контактов
 */

class ds_Contact_Link extends Config
{

    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    function onAfterPost($table, $id, $oldData, $newData)
    {
        $con = GetConnection();

        // Параметры существующих связей
        $select_sql = 'select al1.ContactID as contact1id, '
                . 'al1.ContactLinkID as contact2id, '
                //. 'al1.ContactLinkRoleID as link1id, '
                . 'alr.ReverseLinkRoleID as link2id, '
                . 'al2.ID as id2 '
                . 'from iris_Contact_Link al1 '
                . 'left join iris_ContactLinkRole alr '
                . 'on alr.ID = al1.ContactLinkRoleID '
                . 'left join iris_Contact_Link al2 '
                . 'on al2.ContactID = al1.ContactLinkID '
                . 'and al2.ContactLinkID = al1.ContactID '
                . 'and al2.ContactLinkRoleID = alr.ReverseLinkRoleID '
                . 'where al1.ID = :id';
        $statement = $con->prepare($select_sql);
        $statement->execute(array(':id' => $id));
        $row = $statement->fetch();

        // Если должна быть задана обратная связь, а ее нет, то добавим
        if ($row['link2id'] != '' && $row['id2'] == '') {
            $id = create_guid();
            $insert_sql = 'insert into iris_Contact_Link '
                    . '(id, ContactID, ContactLinkID, ContactLinkRoleID) '
                    . 'values (:p_id, :p_contact_id, :p_contactlink_id, '
                    . ':p_contactlinkrole_id)';
            $statement = $con->prepare($insert_sql);
            $statement->execute(array(
                ':p_id' => $id,
                ':p_contact_id' => $row['contact2id'],
                ':p_contactlink_id' => $row['contact1id'],
                ':p_contactlinkrole_id' =>  $row['link2id']
            ));
        }
    }
}