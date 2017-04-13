<?php

namespace Iris\Config\CRM\Command;

use Iris\Config\CRM\Service\Lock\MutexFactory;
use Iris\Iris;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Iris\Config\CRM\sections\Email\Imap;

/**
 * Read portion of incoming messages
 * Class EmailFetchCommand
 * @package Iris\Config\CRM\Command
 * @usage ./iris iris:email:fetch --mail-account-id=GUID
 */
class EmailFetchCommand extends Command
{
    const DEFAULT_BATCH_SIZE = 10;

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
                'batch-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum amount of messages to read per one reqiuest',
                static::DEFAULT_BATCH_SIZE
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailAccountId = $input->getOption('email-account-id');

        if (!$mailAccountId) {
            throw new \RuntimeException('Email account ID has not been specified');
        }

        $mutex = MutexFactory::create($mailAccountId);
        $mutex->synchronized(function () use ($input, $output, $mailAccountId) {
            $fetcher = new Imap\Fetcher();
            $count = $fetcher->fetch($mailAccountId, $input->getOption('batch-size'));
            $output->writeln(sprintf('Fetched %d mesages', $count));
        });
    }
}