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

    function __construct()
    {
        parent::__construct([
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ]);

        $this->logger = Iris::$app->getContainer()->get('logger.factory')->get('imap');
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

         if (!$incidentId && $this->isSupportMailbox($mailbox["id"])) {
             list($incidentId, $incidentNumber) = $this->insertIncident(
                 $email["subject"], $email["body"], $accountId, $contactId, $ownerId);
             $email["subject"] = $this->addIncidentNumberToSubject($incidentNumber, $email["subject"]);
         }

        $attachments = $this->addFileIdToAttachments($email["attachments"]);

        $body = $this->replaceInlineLinksInBody($email["body"], $email["cidPlaceholders"], $attachments);

        $this->debug("saveEmail subject", $email["subject"]);
        $this->debug("saveEmail attachments", $attachments);
        $this->debug("saveEmail cidPlaceholders", $email["cidPlaceholders"]);

        $emailId = $this->insertEmail($email, $body, $mailbox["id"], $accountId, $contactId, $ownerId, $incidentId);

        $this->insertAttachments($emailId, $accountId, $contactId, $ownerId, $incidentId, $attachments);

        $this->updateLastFetchUid($mailbox["id"], $email["uid"]);
    }

    protected function getEmailLinks($from, $subject)
    {
        // TODO
        return array(null, null, null, null);
    }

    protected function isSupportMailbox($mailboxId)
    {
        // TODO
        return false;
    }

    protected function insertIncident($subject, $body, $accountId, $contactId, $ownerId)
    {
        // TODO
        return null;
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

        // TODO: insert access

        return $emailId;
    }

    protected function insertAttachments($emailId, $accountId, $contactId, $ownerId, $incidentId, $attachments)
    {
        foreach ($attachments as $attachment) {
            $this->insertAttachment($emailId, $accountId, $contactId, $ownerId, $incidentId, $attachment);
        }
    }

    protected function insertAttachment($emailId, $accountId, $contactId, $ownerId, $incidentId, $attachment)
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

        // TODO: insert access

        return $attachment["fileId"];
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
