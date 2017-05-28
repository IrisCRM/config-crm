<?php

namespace Iris\Config\CRM\sections\Email\Fetcher;

use Iris\Config\CRM\sections\Email\EmailFetcher;

class FetcherPop3Adapter implements FetcherInterface
{
    protected $pop3Fetcher;

    public function __construct()
    {
        $this->pop3Fetcher = new EmailFetcher();
    }

    /**
     * @inheritdoc
     */
    public function fetch($emailAccountId, $batchSize)
    {
        return $this->pop3Fetcher->fetchEmail($emailAccountId);
    }

    /**
     * @inheritdoc
     */
    public function syncFlags($emailAccountId)
    {
        // It is not possible for POP3
    }

}