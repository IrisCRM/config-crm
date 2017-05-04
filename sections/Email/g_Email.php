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

    function sendEmail($params) {
        $id = $params['recordId'];
        $mode = $params['sendMode'];

        $con = $this->connection;

        // получение письма по его id
        $sql = <<<EOL
select e_from as from, e_to as to, subject, body, emailaccountid, T1.code as code, T2.sentmailboxname
from iris_email T0
left join iris_emailtype T1 on T0.emailtypeid=T1.id
left join iris_emailaccount T2 on T0.emailaccountid = T2.id
where T0.id=:emailid
EOL;
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":emailid" => $id));
        $email = current($cmd->fetchAll(PDO::FETCH_ASSOC));

        // проверка того, что письмо еще не отправлено
        if ($email['code'] != $mode) {
            return array("status" => "-", "message" => "Разрешено отправлять только исходящие письма");
        }

        // если не указана учетная запись, то вернем ошибку
        if (!$email['emailaccountid']) {
            return array("status" => "-", "message" => "Невозможно отправить письмо, так как у него не задан обратный адрес", "www"=> $email);
        }

        // формируем массив с вложениями с элементами вида (file_name => имя, file_path => путь)
        $sql = <<<EOL
select file_filename, file_file from iris_file 
where emailid=:emailid or id in (select fileid from iris_email_file where emailid=:emailid)
EOL;
        $cmd = $con->prepare($sql);
        $cmd->execute(array(":emailid" => $id));
        $files = $cmd->fetchAll(PDO::FETCH_ASSOC);

        $attachments = array();
        foreach ($files as $file) {
            array_push($attachments, array(
                "file_name" => $file['file_filename'],
                "file_path" => Iris::$app->getRootDir() . 'files/' . $file['file_file'],
            ));
        }

        if ($email["sentmailboxname"]) {
            $mimeMessage = "";
        }

        // отправка письма
        $errm = email_send_message($email['to'], $email['subject'], $email['body'], $email['from'], $attachments, $mimeMessage);
        if ($errm != '') {
            return array("status" => "-", "message" => "Ошибка: ".trim(strip_tags($errm)));
        }

        // проставление статуса "Отправленое" (или "Рассылка - отправленное")
        $sql = "update iris_email set emailtypeid = (select et.id from iris_emailtype et where et.code=:code) where id=:id";
        $cmd = $con->prepare($sql);
        $cmd->execute(array(
            ":id" => $id,
            ":code" => (($mode == 'Outbox') ? 'Sent' : 'Mailing_sent')
        ));

        // сохранение письма в папку "Папка для отправленных" (для imap)
        if ($mimeMessage and $email["sentmailboxname"]) {
            $fetcher = new Imap\Fetcher();
            $fetcher->addMimeMessageToMailbox($email["emailaccountid"], $email["sentmailboxname"], $mimeMessage);
        }

        return array("status" => "+", "message" => "Письмо отправлено");
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
        list($subject, $from, $to, $contactId, $accountId, $hasReaded) = GetFieldValuesByID('Email', $params['id'],
            ['subject', 'e_from', 'e_to', 'contactid', 'accountid', 'has_readed']);
        $contactName = $this->_DB->getRecord($contactId, '{contact}', ['name'])['name'];
        $accountName = $this->_DB->getRecord($accountId, '{account}', ['name'])['name'];

        $stmt = $this->connection->prepare("select id, file_file, file_filename 
            from iris_file 
            where emailid = :email_id");
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

        $userId = GetUserID();
        $userIds = json_decode($hasReaded);
        if (!in_array($userId, is_array($userIds) ? $userIds : [])) {
            $userIds[] = $userId;
            $Fields = FieldValueFormat('has_readed', json_encode($userIds));
            UpdateRecord('Email', $Fields['FieldValues'], $params['id']);
        }

        return [
            'subject' => $subject,
            'from' => $from,
            'to' => $to,
            'contactName' => $contactName,
            'accountName' => $accountName,
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
}
