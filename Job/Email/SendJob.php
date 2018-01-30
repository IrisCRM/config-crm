<?php

namespace Iris\Config\CRM\Job\Email;

use Bernard\Message\AbstractMessage;
use DB;
use Iris\Config\CRM\sections\Email\Imap;
use Iris\Config\CRM\Service\Lock\MutexFactory;
use Iris\Iris;
use Iris\IrisException;
use Iris\Job\AbstractJob;
use Psr\Log\LoggerInterface;

/**
 * Send email using IMAP or POP3 protocol according to email account settings
 * @usage ./iris iris:worker email:send
 */
class SendJob extends AbstractJob
{
    const MUTEX_PREFIX = 'email_send_';

    /**
     * @inheritdoc
     */
    public function handle(AbstractMessage $message)
    {
        if (!function_exists('email_send_message'))
        {
            include_once Iris::$app->getCoreDir() . 'core/engine/emaillib.php';
        }
        $mutex = MutexFactory::create(static::MUTEX_PREFIX . $message->emailAccountId);
        $mutex->synchronized(function () use ($message) {
            /** @var LoggerInterface $logger */
            $logger = Iris::$app->getContainer()->get('logger.factory')->get('email');
            /** @var DB $db */
            $db = Iris::$app->getContainer()->get('db_access');

            $logger->info(sprintf('Sending mail %s...', $message->emailId));

            $sql = <<<SQL
    select
        e_from as from, e_to as to, e_cc as cc, e_bcc as bcc,
        subject, body, emailaccountid,
        T1.code as code, T2.sentmailboxname,
        (select id from iris_emailaccount_mailbox
            where emailaccountid = T2.id
              and name = T2.sentmailboxname) as sentmailboxid
    from iris_email T0
    left join iris_emailtype T1 on T0.emailtypeid=T1.id
    left join iris_emailaccount T2 on T0.emailaccountid = T2.id
    where T0.id=:emailid
SQL;
            $email = current($db->exec($sql, [":emailid" => $message->emailId]));

            // проверка, что письмо еще не отправлено
            if ($email['code'] != $message->mode) {
                $error = "Разрешено отправлять только исходящие письма";
                $logger->error($error);
                throw new IrisException($error);
            }

            // если не указана учетная запись, то ошибку
            if (!$email['emailaccountid']) {
                $error = "Невозможно отправить письмо, так как у него не задан обратный адрес";
                $logger->error($error);
                throw new IrisException($error);
            }

            // формируем массив с вложениями с элементами вида (file_name => имя, file_path => путь)
            $sql = <<<SQL
    select file_filename, file_file from iris_file 
    where emailid=:emailid or id in (select fileid from iris_email_file where emailid=:emailid)
SQL;
            $files = $db->exec($sql, [":emailid" => $message->emailId]);

            $attachments = [];
            foreach ($files as $file) {
                $attachments[] = [
                    "file_name" => $file['file_filename'],
                    "file_path" => Iris::$app->getRootDir() . 'files/' . $file['file_file'],
                ];
            }

            if ($email["sentmailboxname"]) {
                $mimeMessage = "";
            }

            // отправка письма
            $errm = email_send_message($email['to'], $email['subject'], $email['body'], $email['from'],
                $attachments, $mimeMessage, $email['cc'], $email['bcc']);
            if ($errm != '') {
                $logger->error($errm);
                throw new IrisException($errm);
            }

            // проставление статуса "Отправленое" (или "Рассылка - отправленное")
            $sql = "update iris_email 
                set emailtypeid = (select et.id from iris_emailtype et where et.code=:code),
                messagedate = now()
                where id=:id";
            $cmd = $db->connection->prepare($sql);
            $cmd->execute([
                ":id" => $message->emailId,
                ":code" => $message->mode == 'Outbox' ? 'Sent' : 'Mailing_sent'
            ]);

            // сохранение письма в папку "Папка для отправленных" (для imap)
            if ($mimeMessage and $email["sentmailboxname"]) {
                $fetcher = new Imap\Fetcher();
                $uid = $fetcher->addMimeMessageToMailbox($email["emailaccountid"], $email["sentmailboxname"], $mimeMessage);
            }

            // сохранение uid письма для будущей синхронизации
            if ($uid) {
                $sql = "update iris_email set uid = :uid, mailboxid=:mailboxid where id=:id";
                $cmd = $db->connection->prepare($sql);
                $cmd->execute([
                    ":id" => $message->emailId,
                    ":uid" => $uid,
                    ":mailboxid" => $email["sentmailboxid"]
                ]);
            }

            $logger->info(sprintf('Mail %s sent', $message->emailId));
        });
    }
}