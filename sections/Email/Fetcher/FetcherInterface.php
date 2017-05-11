<?php

namespace Iris\Config\CRM\sections\Email\Fetcher;

interface FetcherInterface
{
    /**
     * Fetch new emails from one selected email account
     * @param string $emailAccountId
     * @return mixed
     */
    public function fetch($emailAccountId, $batchSize);

    /**
     * @param $emailAccountId
     * @return mixed
     */
    public function syncFlags($emailAccountId);
}