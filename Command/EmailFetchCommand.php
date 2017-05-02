<?php

namespace Iris\Config\CRM\Command;

use Bernard\QueueFactory\InMemoryFactory;
use Iris\Config\CRM\sections\Email\Imap;
use Iris\Config\CRM\sections\Email\g_Email;
use Iris\Iris;
use Iris\Queue\ConsumingProducer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Read portion of incoming messages
 * Class EmailFetchCommand
 * @package Iris\Config\CRM\Command
 * @usage ./iris iris:email:fetch
 * @usage ./iris iris:email:fetch --mail-account-id=GUID --queue=sync
 */
class EmailFetchCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('iris:email:fetch')
            ->setDescription('Fetch new email messages')
            ->addOption(
                'email-account-id',
                null,
                InputOption::VALUE_REQUIRED,
                'ID of email account'
            )
            ->addOption(
                'queue',
                null,
                InputOption::VALUE_OPTIONAL,
                'Fetch immediately in sync mode'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emailAccountId = $input->getOption('email-account-id');

        if ($input->getOption('queue') == 'sync') {
            $container = Iris::$app->getContainer();

            $container
                ->register('queue.factory', InMemoryFactory::class)
                ->addArgument(new Reference('queue.event_dispatcher'));

            $container
                ->register('queue.producer', ConsumingProducer::class)
                ->addArgument(new Reference('queue.factory'))
                ->addArgument(new Reference('queue.event_dispatcher'))
                ->addArgument(new Reference('queue.consumer'));
        }

        if (!$emailAccountId) {
            $gEmail = new g_Email();
            $gEmail->fetchEmail();
        }
        else {
            $this->dispatch('email:fetch', [
                'emailAccountId' => $emailAccountId
            ]);
        }
    }
}