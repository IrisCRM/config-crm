<?php

namespace Iris\Config\CRM\sections\Emailaccount;

use Config;
use Iris\Iris;
use IrisDomain;

class s_EmailAccount extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('common/Lib/lib.php'));
    }

    public function onAfterPost($table, $id, $OldData, $NewData) {
        $protocolCode = GetArrayValueByName(
            $NewData['FieldValues'], 'fetch_protocol');
        $imapTypeCode = IrisDomain::getDomain('d_fetch_protocol')->
            get('imap', 'code', 'db_value');

        if ($protocolCode == $imapTypeCode && !$OldData) {
            $this->insertDefaultMailbox($id);
        }
    }

    protected function insertDefaultMailbox($id) {
        $sql = "insert into iris_emailaccount_mailbox
            (id, name, displayname, lastuid, emailaccountid, emailtypeid, issync) values
            (iris_genguid(), :name, :displayname, :lastuid, :emailaccountid,
            (select id from iris_emailtype where code=:emailcode), :issync)
        ";
        $cmd =$this->connection->prepare($sql);
        $cmd->execute(array(
            ":name" => "INBOX",
            ":displayname" => "Входящие",
            ":lastuid" => 0,
            ":emailaccountid" => $id,
            ":emailcode" => "Inbox",
            ":issync" => 1,
        ));
    }
}
