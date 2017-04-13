<?php

namespace Iris\Config\CRM\sections\Email\Imap;

/**
 * Fetch emails using IMAP
 * Class Fetcher
 * @package sections\Email\Imap
 */
class Fetcher
{
    /**
     * Fetch new email
     * @param guid $mailAccountId
     * @param int $batchSize Maximum amount of messages to receive per one requeist
     */
    public function fetch($mailAccountId, $batchSize)
    {
        throw new \RuntimeException('Not implemented');
    }
}