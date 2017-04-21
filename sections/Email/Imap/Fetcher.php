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
     * @param int $batchSize Maximum amount of messages to receive per one requeist
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
            $this->debug("mailboxes", $mailboxes);
            if (count($mailboxes) === 0) {
                continue;
            }

            $imapAdapter = new ImapAdapter($emailAccount["server"], $emailAccount["port"], $emailAccount["protocol"],
                $emailAccount["login"], $emailAccount["password"]);
//            foreach ($mailboxes as $mailbox) {
//                $this->debug("mailbox", $mailbox);
//                $fetchResult = $this->fetchMailbox($imapAdapter, $mailbox["name"], $mailbox["lastuid"], $batchSize);
//                $result["isSuccess"] = $result["isSuccess"] && $fetchResult["isSuccess"];
//                $result["messagesCount"] = $result["messagesCount"] + $fetchResult["messagesCount"];
//                if ($result["messagesCount"] >= $batchSize) {
//                    break;
//                }
//            }
        }

        return $result;
    }

    protected function getEmailAccounts($emailAccountId = null)
    {
        $con = $this->connection;

        $sql = "select id, address as server, port, encryption as protocol, login, password from iris_emailaccount 
                where isactive='Y' and isuseimap = 1 and (id = :emailaccountid or :emailaccountid is null)";

        $cmd = $con->prepare($sql);
        $cmd->execute(array(":emailaccountid" => $emailAccountId));
        $this->debug(" getEmailAccounts errorInfo", $cmd->errorInfo());

        return $cmd->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getMailboxes($emailAccount)
    {
        $con = $this->connection;

        $sql = "select name, lastuid from iris_emailaccount_mailbox where emailaccountid = :emailaccountid";

        $cmd = $con->prepare($sql);
        $cmd->execute(array(":emailaccountid" => $emailAccount["id"]));

        return $cmd->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetchMailbox(ImapAdapter $imapAdapter, $mailboxName, $lastUid, $batchSize)
    {
        $imapAdapter->selectMailbox($mailboxName);
        $emails = $imapAdapter->getEmailsFromUid($lastUid, $batchSize);
        file_put_contents('/tmp/emails.txt', var_export($emails, true));

        return array(
            "isSuccess" => is_array($emails),
            "messagesCount" => count($emails) + 12,
        );
    }
    private function debug($caption, $value = null)
    {
        $this->logger->addDebug("$caption " . var_export($value, true));
    }
}