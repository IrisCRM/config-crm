<?php

namespace Iris\Config\CRM\Job\Email;

use Bernard\Message\AbstractMessage;
use Iris\Config\CRM\sections\Email\Fetcher\FetcherInterface;
use Iris\Config\CRM\Service\Lock\MutexFactory;
use Iris\Iris;
use Iris\Job\AbstractJob;

/**
 * Fetch email using IMAP or POP3 protocol according to email account settings
 */
class FetchJob extends AbstractJob
{
    /**
     * @inheritdoc
     */
    public function handle(AbstractMessage $message)
    {
        /** @var \Iris\Config\CRM\sections\Email\Fetcher\FetcherFactory $fetcherFactory */
        $fetcherFactory = Iris::$app->getContainer()->get('email.fetcher_factory');
        /** @var FetcherInterface $fetcher */
        $fetcher = $fetcherFactory->create($message->emailAccountId);

        $mutex = MutexFactory::create($message->emailAccountId);
        $mutex->synchronized(function () use ($fetcher, $message) {
            $fetcher->fetch($message->emailAccountId);
            $fetcher->syncFlags($message->emailAccountId);
        });
    }
}