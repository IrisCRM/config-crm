<?php

namespace Iris\Config\CRM\Job\Email;

use Bernard\Message\AbstractMessage;
use Iris\Config\CRM\sections\Email\EmailFetcher;
use Iris\Config\CRM\sections\Email\Imap as Imap;
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
        // @todo: тут должен обрабатываться один аккаунт, а не все, чтобы можно было в параллельных воркерах быстро читать письма со всех аккаунтов

        // POP3
        $fetcher = new EmailFetcher();
        $popResult =  $fetcher->fetchEmail();

        // IMAP
        $fetcher = new Imap\Fetcher();
        $imapResult = $fetcher->fetch();
        $fetcher->syncFlags();
    }
}