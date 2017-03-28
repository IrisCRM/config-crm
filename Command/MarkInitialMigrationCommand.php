<?php

namespace Iris\Config\CRM\Command;

use DB;
use Iris\Iris;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MarkInitialMigrationCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('iris:mark-initial-migration')
            ->setDescription('Mark initial migration as executed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DB $db */
        $db = Iris::$app->getContainer()->get('DB');

        $db->connection->beginTransaction();

        try {

            $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS doctrine_migration_versions
    (
      version character varying(255) NOT NULL,
      CONSTRAINT doctrine_migration_versions_pkey PRIMARY KEY (version)
    )
SQL;
            $db->exec($sql);

            $sql = <<<SQL
    INSERT INTO doctrine_migration_versions (version) 
    VALUES ('20170304070726') 
    ON CONFLICT DO NOTHING
SQL;
            $db->exec($sql);

            $db->connection->commit();

            $output->writeln('Completed');
        }
        catch (\Exception $e) {
            $output->writeln('Failed');
            $db->connection->rollBack();
            throw $e;
        }
    }
}