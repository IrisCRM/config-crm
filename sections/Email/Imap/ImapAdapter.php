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
        $this->debug("ImapAdapter __construct", $this->connectionString);
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
         $sequence = $startUid . ":" . ($startUid + $batchSize);
         $emailOverviews = imap_fetch_overview($stream, $sequence, FT_UID);
         $result = array();

         foreach ($emailOverviews as $emailOverview) {
             $result[] = $this->mailbox->getMail($emailOverview["uid"], false);
         }

        return $result;
    }

    private function debug($caption, $value = null)
    {
        $this->logger->addDebug("$caption " . var_export($value, true));
    }
}
