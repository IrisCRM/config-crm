<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180412085837 extends AbstractMigration
{

    public function getDescription()
    {
        return "Добавление уникальности для iris_contact_token.code";
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE iris_contact_token ADD CONSTRAINT code_must_be_unique UNIQUE (code);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE iris_contact_token DROP CONSTRAINT code_must_be_unique;");
    }
}
