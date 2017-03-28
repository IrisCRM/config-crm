<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Loader;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170304070726 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = file_get_contents(Loader::getLoader()->getNamespaceDir('\\Iris\\Config\\CRM') . 'migrations/iriscrm.sql');
        $this->connection->getWrappedConnection()->exec($sql);
    }

    /**
     * @param Schema $schema
     * @throws \Exception
     */
    public function down(Schema $schema)
    {
        throw new \Exception('Cant down this migration');
    }
}
