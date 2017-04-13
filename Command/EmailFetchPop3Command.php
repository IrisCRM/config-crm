<?php

namespace Iris\Config\CRM\Command;

use Iris\Iris;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Iris\Config\CRM\sections\Email;

class EmailFetchPop3Command extends Command
{
    protected function configure()
    {
        $this
            ->setName('iris:email:fetch-pop3')
            ->setDescription('Fetch new email messages via pop3 protocol')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fetcher = new Email\EmailFetcher();
        $result = $fetcher->fetchEmail();
        $output->writeln(json_encode($result));
    }
}