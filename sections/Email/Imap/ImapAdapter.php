<?php

namespace Iris\Config\CRM\sections\Email\Imap;

use Config;
use Iris\Iris;
use PDO;

use PhpImap\Mailbox;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;

class ImapAdapter
{
    protected $connectionString;
    protected $tempDir;
    protected $mailbox;
    protected $logger;

    function __construct($server, $port, $protocol, $login, $password)
    {
        $this->logger = Iris::$app->getContainer()->get('logger.factory')->get('imap');

        $this->tempDir = $this->createTempDir();
        $this->connectionString = $this->getConnectionString($server, $port, $protocol);
        $this->debug("ImapAdapter __construct", $this->tempDir);
        $this->mailbox = new Mailbox($this->connectionString, $login, $password, $this->tempDir);
        $this->debug("ImapAdapter __construct ok");
    }

    protected function createTempDir()
    {
        $items = explode('\\', get_class($this));
        $tempName = tempnam(sys_get_temp_dir(), array_pop($items));
        if (file_exists($tempName)) {
            unlink($tempName);
        }
        mkdir($tempName);

        if (is_dir($tempName)) {
            return $tempName;
        }
    }

    protected function getConnectionString($server, $port, $protocol)
    {
        return "{" . $server . ":" . $port . "/imap" . ($protocol ? "/" . $protocol : "") . "}";
    }

    protected function convertMailboxName($mailboxName)
    {
        return mb_convert_encoding($mailboxName, "UTF7-IMAP", "UTF-8");
    }

    public function addMimeMessageToMailbox($mailboxName, $MimeMessage)
    {
        return imap_append(
            $this->mailbox->getImapStream(),
            $this->connectionString . $this->convertMailboxName($mailboxName),
            $MimeMessage,
            "\\Seen");
    }

    public function markMailAsRead($uid)
    {
        return $this->mailbox->markMailAsRead($uid);
    }

    public function triggerMailImportantState($uid, $isImportant)
    {
        if ($isImportant) {
            return $this->mailbox->markMailAsImportant($uid);
        }

        return $this->mailbox->clearFlag(array($uid), '\\Flagged');
    }

    public function selectMailbox($mailboxName)
    {
        $this->mailbox->switchMailbox(
            $this->connectionString . $this->convertMailboxName($mailboxName));
    }

    public function getEmailsFromUid($uid, $batchSize)
    {
         $stream = $this->mailbox->getImapStream();
         $startUid = ($uid ? $uid : 1);
         $sequence = $startUid . ":*";
         $this->debug("ImapAdapter getEmailsFromUid sequence", $sequence);

         $emailOverviews = imap_fetch_overview($stream, $sequence, FT_UID);
         $result = array();

         if (count($emailOverviews) > $batchSize) {
             $emailOverviews = array_slice($emailOverviews, 0, $batchSize);
         }

         foreach ($emailOverviews as $emailOverview) {
             // set unique attachments dir for each email to avoid possible name coincidence
             $this->mailbox->setAttachmentsDir($this->getDirForUid($emailOverview->uid));
             $email = $this->mailbox->getMail($emailOverview->uid, false);
             $result[] = $this->convertIncomingMailToArray($email, $emailOverview);
         }

        return $result;
    }

    protected function convertIncomingMailToArray($email, $emailOverview)
    {
        $result = array(
            "uid" => $email->id,
            "messageId" => $email->messageId,
            "date" => date_format(date_create_from_format('Y-m-d H:i:s', $email->date), 'd.m.Y H:i:s'),
            "from" => $email->fromAddress,
            "to" => $email->toString,
            "subject" => $email->subject,
            "flagged" => $emailOverview->flagged,
            "seen" => $emailOverview->seen,
            "body" => $email->textHtml,
            "attachments" => array(),
        );

        foreach ($email->getAttachments() as $attachment) {
            $result["attachments"][] = array(
                "attachmentId" => $attachment->id,
                "fileName" => $attachment->name,
                "filePath" => $attachment->filePath,
            );
        }

        $result["cidPlaceholders"] = $email->getInternalLinksPlaceholders();

        return $result;
    }

    protected function getDirForUid($uid)
    {
        $result = $this->tempDir . "/" . $uid;
        mkdir($result);

        return $result;
    }

    private function debug($caption, $value = null)
    {
        $this->logger->addDebug("$caption " . var_export($value, true));
    }

    protected static function deleteDir($dirPath) {
        if (!is_dir($dirPath)) {
            throw new \InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    function __destruct()
    {
        self::deleteDir($this->tempDir);
    }
}
