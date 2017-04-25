<?php

namespace Iris\Config\CRM\sections\Email\Imap;

use Config;
use Iris\Iris;
use PDO;

/**
 * Fetch emails using IMAP
 * Class Fetcher
 * @package sections\Email\Imap
 */
class Fetcher extends Config
{
    protected $logger;
    private $supportEmails = array();
    const MAX_INCIDENT_BODY_LENGTH = 1000;

    function __construct()
    {
        parent::__construct([
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ]);

        $this->supportEmails = $this->getSupportEmails();
        $this->logger = Iris::$app->getContainer()->get('logger.factory')->get('imap');
    }

    // TODO: copied from EmailFetcher class
    protected function getSupportEmails() {
        $result = array();

        $res = $this->connection->query("select stringvalue as value from iris_systemvariable
          where code='support_email_addresses'")->fetchAll(PDO::FETCH_ASSOC);
        $emails = $res[0]['value'];
        if ($emails != '') {
            $emails = iris_str_replace(' ', '', $emails);
            $result = explode(',', $emails);
        }

        return $result;
    }

    /**
     * Fetch new email
     * @param guid $emailAccountId
     * @param int $batchSize Maximum amount of messages to receive per one request
     */
    public function fetch($emailAccountId = null, $batchSize = 10)
    {
        $result = array(
            "isSuccess" => true,
            "messagesCount" => 0,
        );

        $this->debug("");
        $this->debug("fetch emailAccountId", $emailAccountId);
        $emailAccounts = $this->getEmailAccounts($emailAccountId);
        $this->debug("", $emailAccounts);
        foreach ($emailAccounts as $emailAccount) {
            $this->debug("emailAccount",$emailAccount);

            $mailboxes = $this->getMailboxes($emailAccount);
            if (count($mailboxes) === 0) {
                continue;
            }

            $imapAdapter = new ImapAdapter($emailAccount["server"], $emailAccount["port"], $emailAccount["protocol"],
                $emailAccount["login"], $emailAccount["password"]);
            foreach ($mailboxes as $mailbox) {
                $this->debug("mailbox", $mailbox);

                $fetchResult = $this->fetchMailbox($imapAdapter, $mailbox, $mailbox["lastuid"] + 1,
                    $batchSize - $result["messagesCount"]);
                $result["isSuccess"] = $result["isSuccess"] && $fetchResult["isSuccess"];
                $result["messagesCount"] = $result["messagesCount"] + $fetchResult["messagesCount"];
                if ($result["messagesCount"] >= $batchSize) {
                    break;
                }
            }
        }

        return $result;
    }

    protected function getEmailAccounts($emailAccountId = null)
    {
        $con = $this->connection;

        $sql = "select id, address as server, port, encryption as protocol, login, password from iris_emailaccount 
                where isactive='Y' and fetch_protocol = 2 and (id = :emailaccountid or :emailaccountid is null)";

        $cmd = $con->prepare($sql);
        $cmd->execute(array(":emailaccountid" => $emailAccountId));

        return $cmd->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getMailboxes($emailAccount)
    {
        $con = $this->connection;

        $sql = "select id, name, lastuid from iris_emailaccount_mailbox where emailaccountid = :emailaccountid";

        $cmd = $con->prepare($sql);
        $cmd->execute(array(":emailaccountid" => $emailAccount["id"]));

        return $cmd->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetchMailbox(ImapAdapter $imapAdapter, $mailbox, $lastUid, $batchSize)
    {
        $imapAdapter->selectMailbox($mailbox["name"]);
        $emails = $imapAdapter->getEmailsFromUid($lastUid, $batchSize);
        $this->debug("getEmailsFromUid count", count($emails));
        $messagesCount = 0;

        foreach ($emails as $email) {
            $this->debug("fetch email", array($email["uid"], $email["from"], $email["subject"]));
            if (!$this->isNewEmail($mailbox["id"], $email["uid"])) {
                $this->debug("isNewEmail false");
                 continue;
            }
            $this->saveEmail($mailbox, $email);
            $messagesCount++;
        }

        return array(
            "isSuccess" => is_array($emails),
            "messagesCount" => $messagesCount,
        );
    }

    protected function isNewEmail($mailboxId, $uid)
    {
        $cmd = $this->connection->prepare("select id from iris_email where mailboxid = :mailboxid and uid = :uid");
        $cmd->execute(array(
            ":mailboxid" => $mailboxId,
            ":uid" => $uid,
        ));
        $data = $cmd->fetchAll(PDO::FETCH_ASSOC);

        return count($data) < 1;
    }

    protected function saveEmail($mailbox, $email)
    {
        list ($accountId, $contactId, $ownerId, $incidentId) = $this->getEmailLinks($email["from"], $email["subject"]);

         if (!$incidentId && $this->isSupportEmail($email["to"])) {
             list($incidentId, $incidentNumber) = $this->insertIncident(
                 $mailbox["id"], $email["subject"], $email["body"], $accountId, $contactId, $ownerId);
             $email["subject"] = $this->addIncidentNumberToSubject($incidentNumber, $email["subject"]);
         }

        $attachments = $this->addFileIdToAttachments($email["attachments"]);

        $body = $this->replaceInlineLinksInBody($email["body"], $email["cidPlaceholders"], $attachments);

        $this->debug("saveEmail subject", $email["subject"]);
        $this->debug("saveEmail attachments", $attachments);
        $this->debug("saveEmail cidPlaceholders", $email["cidPlaceholders"]);

        $emailId = $this->insertEmail($email, $body, $mailbox["id"], $accountId, $contactId, $ownerId, $incidentId);

        $this->insertAttachments($emailId, $mailbox["id"], $accountId, $contactId, $ownerId, $incidentId, $attachments);

        $this->updateLastFetchUid($mailbox["id"], $email["uid"]);
    }

    protected function getEmailLinks($from, $subject)
    {
        $cmd = $this->connection->prepare("select id as contactid, accountid, ownerid from iris_contact
            where email=:email or id in (select contactid from iris_contact_email where email = :email)");
        $cmd->execute(array(
            ":email" => $from,
        ));
        $data = current($cmd->fetchAll(pdo::FETCH_ASSOC));

        return array($data["accountid"], $data["contactid"], $data["ownerid"], $this->getIncidentId($subject));
    }

    protected function getIncidentId($subject)
    {
        $matches = array();
        $isFind = mb_ereg("\\[\\d{6}-\\d+\\]", $subject, $matches);

        $this->debug("getIncidentId match", array($subject, $matches));
        if (!$isFind) {
            return null;
        }

        $incidentNumber = trim($matches[0], "\x5B..\x5D"); // обрезаем скобки [ и ]
        $cmd = $this->connection->prepare("select id as id from iris_incident where number = :number");
        $cmd->execute(array(":number" => $incidentNumber));
        $data = current($cmd->fetchAll(PDO::FETCH_ASSOC));

        return $data["id"];
    }

    protected function isSupportEmail($email)
    {
        $this->debug("isSupportEmail match", array($this->supportEmails, $email));
        return in_array($email, $this->supportEmails) === true;
    }

    // TODO: use method in EmailFetcher
    protected function insertIncident($mailboxId, $subject, $body, $accountId, $contactId, $ownerId)
    {
        $incidentNumber = GenerateNewNumber('IncidentNumber', null, $this->connection);
        $incidentId = create_guid();

        // сформируем текстовое содержимое письма, которое не превышает 1000 символов
        $shortBody = $body;
        $shortBody = iris_str_replace(chr(13).chr(10), '', $shortBody);
        $shortBody = iris_str_replace(chr(10).chr(13), '', $shortBody);
        $shortBody = iris_str_replace('<br>', chr(10), $shortBody);
        $shortBody = iris_str_replace('<BR>', chr(10), $shortBody);
        $shortBody = strip_tags($shortBody);
        if (iris_strlen($shortBody) >= self::MAX_INCIDENT_BODY_LENGTH) {
            $shortBody = iris_substr($shortBody, 0, self::MAX_INCIDENT_BODY_LENGTH);
        }

        $sql = "insert into iris_incident (id, number, name, description, accountid, contactid, ownerid, date,
          incidentstateid, isremind, reminddate) values (:id, :number, :name, :description, :accountid,
          :contactid, :ownerid, now(), (select id from iris_incidentstate where code='Plan'), 1, now())";
        $cmd = $this->connection->prepare($sql);
        $cmd->execute(array(
            ":id" => $incidentId,
            ":number" => $incidentNumber,
            ":name" => $subject,
            ":description" => $shortBody,
            ":accountid" => $accountId,
            ":contactid" => $contactId,
            ":ownerid" => $ownerId
        ));

        if ($cmd->errorCode() != '00000') {
            throw new \RuntimeException('Incident is not inserted: ' . var_export($cmd->errorInfo(), true));
        }

        // увеличим номер инцидента
        UpdateNumber('Incident', $incidentId, 'IncidentNumber');

        $this->insertAccess("iris_incident", $incidentId, $mailboxId);

        return array($incidentId, $incidentNumber);
    }

    protected function addIncidentNumberToSubject($incidentNumber, $subject)
    {
        return "[" . $incidentNumber . "] " . $subject;
    }

    protected function addFileIdToAttachments($attachments)
    {
        $result = array();
        $buffer = null;
        foreach ($attachments as $attachment) {
            $buffer = $attachment;
            $buffer["fileId"] = create_guid();
            $result[] = $buffer;
        }

        return $result;
    }

    protected function replaceInlineLinksInBody($body, $cidPlaceholders, $attachments)
    {
        $result = $body;

        foreach ($attachments as $attachment) {
            $placeholder = $cidPlaceholders[$attachment["attachmentId"]];
            if (empty($placeholder)) {
                continue;
            }

            $replaceTo = "web.php?_func=DownloadFile&table=iris_File&id=".$attachment["fileId"]."&column=file_file";
            $result = str_replace($placeholder, $replaceTo, $result);
        }

        return $result;
    }

    protected function insertEmail($email, $body, $mailboxId, $accountId, $contactId, $ownerId, $incidentId)
    {
        $emailId = create_guid();

        $sql = "insert into iris_email(id, createid, createdate, uid, e_from, e_to, subject, body, emailtypeid,
            mailboxid, accountid, contactid, ownerid, messagedate, incidentid) 
            values (:id, :createid, now(), :uid, :e_from, :e_to, :subject, :body,
            (select id from iris_emailtype where code='Inbox'), :mailboxid,
            :accountid, :contactid, :ownerid, to_timestamp(:messagedate, 'DD.MM.YYYY HH24:MI:SS'), :incidentid)";

        $cmd = $this->connection->prepare($sql);
        $cmd->execute(array(
            ":id" => $emailId,
            ":createid" => $this->_User->property('id'),
            ":uid" => $email["uid"],
            ":e_from" => $email["from"],
            ":e_to" => $email["to"],
            ":subject" => $email["subject"],
            ":body" => $body,
            ":accountid" => $accountId,
            ":mailboxid" => $mailboxId,
            ":contactid" => $contactId,
            ":ownerid" => $ownerId,
            ":messagedate" => $email["date"],
            ":incidentid" => $incidentId,
        ));

        if ($cmd->errorCode() != '00000') {
            throw new \RuntimeException('Email is not inserted: ' . var_export($cmd->errorInfo(), true));
        }

        $this->insertAccess("iris_email", $emailId, $mailboxId);

        return $emailId;
    }

    protected function insertAttachments($emailId, $mailboxId, $accountId, $contactId, $ownerId, $incidentId, $attachments)
    {
        foreach ($attachments as $attachment) {
            $this->insertAttachment($emailId, $mailboxId, $accountId, $contactId, $ownerId, $incidentId, $attachment);
        }
    }

    protected function insertAttachment($emailId, $mailboxId, $accountId, $contactId, $ownerId, $incidentId, $attachment)
    {
        $fileRealName = create_guid();
        $fileRealPath = Iris::$app->getRootDir() . 'files/' . $fileRealName;

        $sql = "insert into iris_file (id, createdate, file_file, file_filename, 
            emailId, accountId, contactId, ownerId, incidentId, fileStateId, date) values 
            (:id, now(), :file_file, :file_filename, :emailid, :accountid, 
            :contactid, :ownerid, :incidentid, (select id from iris_filestate where code = 'Active'), now())";
        $cmd = $this->connection->prepare($sql);

        $cmd->execute(array(
            ":id" => $attachment["fileId"],
            ":file_file" => $fileRealName,
            ":file_filename" => $attachment["fileName"],
            ":emailid" => $emailId,
            ":accountid" => $accountId,
            ":contactid" => $contactId,
            ":ownerid" => $ownerId,
            ":incidentid" => $incidentId,
        ));

        if ($cmd->errorCode() != '00000') {
            throw new \RuntimeException('Attachment is not inserted: ' . var_export($cmd->errorInfo(), true));
        }

        if (!rename($attachment["filePath"], $fileRealPath)) {
            throw new \RuntimeException('Attachment rename error');
        }

        $this->insertAccess("iris_file", $attachment["fileId"], $mailboxId);

        return $attachment["fileId"];
    }

    protected function insertAccess($tableName, $recordId, $mailboxId)
    {
        $sql = "insert into ".$tableName."_access (ID, RecordID, ContactID, R, W, D, A) 
          select iris_genguid() as id, :recordid as recordid, contactid, r, w, d, a from iris_emailaccount_defaultaccess
          where emailaccountid = (select emailaccountid from iris_emailaccount_mailbox where id=:mailboxid)";

        $cmd = $this->connection->prepare($sql);
        $cmd->execute(array(
            ":recordid" => $recordId,
            ":mailboxid" => $mailboxId,
        ));
    }

    protected function updateLastFetchUid($mailboxId, $uid)
    {
        $cmd = $this->connection->prepare("update iris_emailaccount_mailbox set lastuid=:uid where id=:id");
        $cmd->execute(array(
            ":id" => $mailboxId,
            ":uid" => $uid,
        ));
    }

    private function debug($caption, $value = null)
    {
        $this->logger->addDebug("$caption " . var_export($value, true));
    }
}
