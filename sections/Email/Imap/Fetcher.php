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

    function __construct()
    {
        parent::__construct([
            'common/Lib/lib.php',
            'common/Lib/access.php',
        ]);
    }


    /**
     * Fetch new email
     * @param guid $mailAccountId
     * @param int $batchSize Maximum amount of messages to receive per one requeist
     */
    public function fetch($mailAccountId, $batchSize)
    {
//        throw new \RuntimeException('Not implemented');
        return array(
            "isSuccess" => true,
            "messagesCount" => 0,
        );
    }
}