<?php
//********************************************************************
// Раздел E-mail. таблица записей
//********************************************************************

namespace Iris\Config\CRM\sections\Email;

use Config;
use Iris\Iris;
use Iris\Queue\DispatchesJobs;
use PDO;
use Iris\Config\CRM\sections\Email\Imap as Imap;
use IrisDomain;

include_once Iris::$app->getCoreDir() . 'core/engine/emaillib.php';

class g_Email extends Config
{
    use DispatchesJobs;

    function __construct()
    {
        parent::__construct(array(
            'common/Lib/lib.php',
            'common/Lib/access.php'
        ));
    }

    function triggerStar($params) {
        $emailId = $params['recordId'];
        $currentValue = $params['currentValue'];
        $newValue = !$currentValue;
        $permissions = array();
        $emailInfo = $this->getEmailInfo($emailId);

        // права RW
        GetUserRecordPermissions('iris_email', $emailId, GetUserId(), $permissions);
        if (($permissions['r'] == 0) or ($permissions['w'] == 0)) {
            return array("success" => 0);
        }

        if ($emailInfo["isimap"] == 1) {
            $fetcher = new Imap\Fetcher();
            $fetcher->triggerMailImportantState(
                $emailInfo["emailaccountid"], $emailInfo["mailboxname"], $emailInfo["uid"], $newValue);
        }

        $val = (int)$newValue;
        $con = $this->connection;
        $cmd = $con->prepare("update iris_email set isimportant = :val where id=:id");
        $cmd->execute(array(":id" => $emailId, ":val" => $val));
        $success = ($cmd->errorCode() == '00000' ? 1 : 0);

        return array("success" => $success, "currentValue" => $currentValue, "val" => $val);
    }

    function getEmailAnswers($params) {
        $emailId = $params['recordId'];
        $emailInfo = $this->getEmailInfo($emailId);

        // права R
        GetUserRecordPermissions('iris_email', $emailId, GetUserId(), $permissions);
        if ($permissions['r'] == 0) {
            return array("success" => 0);
        }

        $con = $this->connection;
        $cmd = $con->prepare("select id from iris_email where parentemailid=:id");
        $cmd->execute(array(":id" => $emailId));
        $ids = $cmd->fetchAll(PDO::FETCH_COLUMN, 0);
        $success = ($cmd->errorCode() == '00000' ? 1 : 0);

        return array("success" => $success, "answersIds" => $ids);
    }

    function sendEmail($params) {
        $this->dispatch('email:send', [
            'emailId' => $params['recordId'],
            'mode' => $params['sendMode']
        ]);
        return [
            "status" => "+",
            "message" => "Письмо поставлено в очередь на отправку",
        ];
    }

    function fetchEmail($params = null) {
        foreach ($this->getEmailAccountIds() as $emailAccountId) {
            $this->dispatch('email:fetch', [
                'emailAccountId' => $emailAccountId
            ]);
        }
    }

    /**
     * Get all active email accounts
     * @return array
     */
    protected function getEmailAccountIds()
    {
        $sql = "select id 
            from iris_emailaccount 
            where isactive='Y'";
        $cmd = $this->connection->prepare($sql);
        $cmd->execute();
        return $cmd->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getMailData($params)
    {
        list($subject, $from, $to, $cc, $bcc, $contactId, $accountId, $hasReaded, $emailTypeId) =
            GetFieldValuesByID('Email', $params['id'], [
                'subject', 'e_from', 'e_to', 'e_cc', 'e_bcc', 'contactid', 'accountid', 'has_readed', 'emailtypeid',
            ]);
        $contactName = $this->_DB->getRecord($contactId, '{contact}', ['name'])['name'];
        $accountName = $this->_DB->getRecord($accountId, '{account}', ['name'])['name'];
        $emailTypeCode = $this->_DB->getRecord($emailTypeId, '{emailtype}', ['code'])['code'];

        $stmt = $this->connection->prepare("select
            T0.id as id, T0.file_file as file_file, T0.file_filename as file_filename
            from iris_file T0
            where T0.emailid = :email_id
              or T0.id in (select T1.fileid from iris_email_file T1 where T1.emailid=:email_id)");
        $stmt->execute([
            ':email_id' => $params['id'],
        ]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($files as &$file) {
            $file['download_url'] = getFileDownloadUrl(GetTableName('{File}'), $file['id'], 'file_file');
            $fileparts = explode('.', $file['file_filename']);
            $file['extension'] = $fileparts ? array_pop($fileparts) : null;
            $file['name'] = implode('.', $fileparts);
        }
        
        $this->updateReaders([
            'recordId' => $params['id'],
        ]);

        return [
            'subject' => $subject,
            'from' => $from,
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'contactName' => $contactName,
            'accountName' => $accountName,
            'emailTypeCode' => $emailTypeCode,
            'id' => $params['id'],
            'files' => $files,
        ];
    }

    function updateReaders($params) {
        $emailId = $params["recordId"];
        $userId = $this->_User->property("id");
        $emailInfo = $this->getEmailInfo($emailId);

        if (empty($emailInfo["has_readed"])) {
            $emailInfo["has_readed"] = "[]";
        }

        $readers = json_decode($emailInfo["has_readed"], true);

        if (in_array($userId, $readers) or $emailInfo["isimap"] === 0) {
            return array("isSuccess" => true);
        }

        array_push($readers, $userId);

        if ($userId === $emailInfo["ownerid"]) {
            $fetcher = new Imap\Fetcher();
            $fetcher->markMailAsRead($emailInfo["emailaccountid"], $emailInfo["mailboxname"], $emailInfo["uid"]);
        }

        $cmd = $this->connection->prepare("update iris_email set has_readed = :readedstr  where id = :id");
        $cmd->execute(array(
            ":readedstr" => json_encode($readers),
            ":id" => $emailId,
        ));

        return array("isSuccess" => true);
    }

    function deleteFromImapServer($emailId) {
        $emailInfo = $this->getEmailInfo($emailId);
        if (!$emailInfo["isimap"] or !$emailInfo["isinbox"] or !$emailInfo["isdeletefromserver"]) {
            return array("isSuccess" => true);
        }

        $fetcher = new Imap\Fetcher();
        $fetcher->deleteMail($emailInfo["emailaccountid"], $emailInfo["mailboxname"], $emailInfo["uid"]);
    }

    function testConnection($params) {
        $emailAccountId = $params["emailAccountId"];
        $imapTypeCode = IrisDomain::getDomain('d_fetch_protocol')->
            get('imap', 'code', 'db_value');

        if ($this->getEmailAccountType($emailAccountId) != $imapTypeCode) {
            return [
                "isSuccess" => false,
                "message" => "Доступно только для протокола IMAP"
            ];
        }

        $fetcher = new Imap\Fetcher();
        $result = $fetcher->getEmailAccountStatus($emailAccountId);

        if (!$result) {
            return [
                "isSuccess" => false,
                "message" => "Не удалось установить соединение"
            ];
        }

        return $result;
    }

    protected function getEmailInfo($emailId)
    {
        $sql = "select T0.uid, T0.has_readed, MB.name as mailboxname, MB.emailaccountid, EA.ownerid,
          case when EA.fetch_protocol = 2 then 1 else 0 end as isimap,
          case when ET.code = 'Inbox' then 1 else 0 end as isinbox, EA.isdeletefromserver
          from iris_email T0
          left join iris_emailaccount_mailbox MB on T0.mailboxid = MB.id
          left join iris_emailaccount EA on MB.emailaccountid = EA.id
          left join iris_emailtype ET on T0.emailtypeid = ET.id
          where T0.id = :id";
        $cmd = $this->connection->prepare($sql);
        $cmd->execute(array(":id" => $emailId));
        return current($cmd->fetchAll(PDO::FETCH_ASSOC));
    }

    protected function getEmailAccountType($emailAccountId)
    {
        $sql = "select fetch_protocol 
            from iris_emailaccount 
            where id = :email_account_id";
        $cmd = $this->connection->prepare($sql);
        $cmd->execute([
            ':email_account_id' => $emailAccountId
        ]);

        return $cmd->fetch(PDO::FETCH_COLUMN);
    }
}
