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

    public function selectMailbox($mailboxName)
    {
        $this->mailbox->switchMailbox(
            $this->connectionString . mb_convert_encoding($mailboxName, "UTF7-IMAP", "UTF-8"));
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
             // TODO: change attachments dir via setAttachmentsDir
             $email = $this->mailbox->getMail($emailOverview->uid, false);
             $result[] = $this->convertIncomingMailToArray($email);
         }

        return $result;
    }

    protected function convertIncomingMailToArray($email)
    {
        $result = array(
            "uid" => $email->id,
            "messageId" => $email->messageId,
            "date" => date_format(date_create_from_format('Y-m-d H:i:s', $email->date), 'd.m.Y H:i:s'),
            "from" => $email->fromAddress,
            "to" => $email->toString,
            "subject" => $email->subject,
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

    private function debug($caption, $value = null)
    {
        $this->logger->addDebug("$caption " . var_export($value, true));
    }

    function __destruct()
    {
        array_map('unlink', glob($this->tempDir . "/*.*"));
        rmdir($this->tempDir);
    }
}
