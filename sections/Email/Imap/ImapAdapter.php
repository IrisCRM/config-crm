<?php

namespace Iris\Config\CRM\sections\Email\Imap;

use Config;
use Iris\Iris;
use PDO;


class ImapAdapter
{
    protected $connectionString;
    protected $tempDir;
    protected $mailbox;
    protected $mailboxNextUid;
    protected $mailboxName = "INBOX";
    protected $logger;

    function __construct($server, $port, $protocol, $login, $password)
    {
        $this->logger = Iris::$app->getContainer()->get('logger.factory')->get('imap');

        $this->tempDir = $this->createTempDir();
        $this->connectionString = $this->getConnectionString($server, $port, $protocol);
        $this->debug("ImapAdapter __construct", $this->tempDir .', ' . $this->connectionString . ', ' . $login);
        $this->mailbox = new MailboxPached($this->connectionString, $login, $password, $this->tempDir);
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
        return "{" . $server . ":" . $port . "/imap" . ($protocol ? "/" . $protocol . "/novalidate-cert" : "") . "}";
    }

    public function addMimeMessageToMailbox($mailboxName, $MimeMessage)
    {
        return imap_append(
            $this->mailbox->getImapStream(),
            $this->getMailboxFullName($mailboxName),
            $MimeMessage,
            "\\Seen");
    }

    protected function getMailboxFullName($mailboxName) {
        return $this->connectionString . $mailboxName;
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

    public function deleteMail($uid)
    {
        return $this->mailbox->deleteMail($uid);
    }

    public function selectMailbox($mailboxName)
    {
        $this->switchMailbox($mailboxName);
        $status = $this->getMailboxStatus($mailboxName);
        $this->mailboxNextUid = $status->uidnext;
    }

    protected function switchMailbox($mailboxName) {
        $this->mailboxName = $mailboxName;
        $this->mailbox->switchMailbox(
            $this->getMailboxFullName($this->mailboxName));
    }

    protected function getMailboxStatus($mailboxName) {
        try {
            $this->mailbox->switchMailbox(
                $this->getMailboxFullName($mailboxName));
            return $this->mailbox->statusMailbox();
        } catch (Throwable $exception) {
            return null;
        }
    }

    public function getMailboxesStatus() {
        if (!$this->isConnected()) {
            return null;
        }

        $mailboxNames = $this->getMailboxNames();
        $result = [];

        foreach($mailboxNames as $mailboxName) {
            $result[] = array(
                "name" => $mailboxName,
                "status" => $this->getMailboxStatus($mailboxName),
            );
        }

        return $result;
    }

    public function isConnected() {
        try {
            $stream = $this->mailbox->getImapStream();
            return true;
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function getMailboxNames() {
        $items = $this->mailbox->getMailboxes();
        $result = [];

        foreach($items as $item) {
            array_push($result, $item["shortpath"]);
        }

        return $result;     

    }

    public function getEmailsOverview($mailboxName)
    {
        $result = array();
        $this->selectMailbox($mailboxName);
        $emailOverviews = $this->getEmailsOverviewFromUid(1, 0);

        foreach($emailOverviews as $emailOverview) {
            $result[] = array(
                "uid" => $emailOverview->uid,
                "seen" => $emailOverview->seen,
                "flagged" => $emailOverview->flagged,
            );
        }

        return $result;
    }

    public function getEmailsFromUid($uid, $batchSize)
    {
        $emailOverviews = $this->getEmailsOverviewFromUid($uid, $batchSize);
        $result = array();

        foreach ($emailOverviews as $emailOverview) {
            $this->debug("getEmailsFromUid mailbox", [$this->mailboxName]);
            $this->debug("getEmailsFromUid emailOverview", $emailOverview);
            // set unique attachments dir for each email to avoid possible name coincidence
            $this->mailbox->setAttachmentsDir($this->getDirForUid($emailOverview->uid));
            $email = $this->mailbox->getMail($emailOverview->uid, false);
            $result[] = $this->convertIncomingMailToArray($email, $emailOverview);
        }

        return $result;
    }

    protected function getEmailsOverviewFromUid($uid, $batchSize)
    {
        $stream = $this->mailbox->getImapStream();
        $startUid = ($uid ? $uid : 1);

        $mailboxFinalUid = $this->mailboxNextUid - 1;
        if ($uid > $mailboxFinalUid) {
            $this->debug("ImapAdapter getEmailsOverviewFromUid: ",
                "start uid is greater than final uid, skip mailbox");
            return [];
        }

        $finals = [];
        $multiple = 1;
        while (true) {
            $finalUid = $startUid + $multiple * (!$batchSize ? 1 : $batchSize);
            $finals[] = $finalUid;
            $multiple *= 10;

            if ($finalUid > $mailboxFinalUid) {
                $finals[count($finals) - 1] = $mailboxFinalUid;
                break;
            }
        }
        if ($batchSize == 0) {
            $finals = [$mailboxFinalUid]; // for sync flags
        }

        foreach ($finals as $final) {
            $sequence = $startUid . ":" . $final;
            $this->debug("ImapAdapter getEmailsOverviewFromUid sequence", $sequence);

            $result = imap_fetch_overview($stream, $sequence, FT_UID);
            $this->debug("ImapAdapter getEmailsOverviewFromUid imap_fetch_overview OK. count:", count($result));

            if (count($result) > 0) {
                if ($batchSize > 0 and count($result) > $batchSize) {
                    $result = array_slice($result, 0, $batchSize);
                }

                return $result;
            }
        }

        return [];
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
            "body" => !empty($email->textHtml) ? $email->textHtml : $this->convertLineEndingsToBR($email->textPlain),
            "attachments" => array(),
        );

        foreach ($email->getAttachments() as $attachment) {
            $result["attachments"][] = array(
                "contentId" => $attachment->contentId,
                "fileName" => $attachment->name,
                "filePath" => $attachment->filePath,
            );
        }

        $result["cidPlaceholders"] = $email->getInternalLinksPlaceholders();

        return $result;
    }

    protected function convertLineEndingsToBR($string)
    {
        return nl2br($string);
    }

    protected function getDirForUid($uid)
    {
        $result = $this->tempDir . "/" . $this->mailboxName . "/" . $uid;
        mkdir($result, 0777, true);

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
