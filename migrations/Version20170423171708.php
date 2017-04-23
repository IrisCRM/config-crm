<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Переименование поля isuseimap в fetch_protocol
 */
class Version20170423171708 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("update iris_Table_Column set TableID='1fb8b784-40dd-4039-a616-c8485dfea753', Name='Протокол считывания писем', Code='fetch_protocol', IsDuplicate='0', DefaultValue=NULL, fkName=NULL, fkTableID=NULL, pkName=NULL, IndexName='iris_emailaccount_fetch_protocol_i', ColumnTypeID='687bc1a7-de12-ab78-11bd-6936d9a9ff75', isNotNull='0', OnDeleteID=NULL, OnUpdateID=NULL, Description=NULL, modifyid='005405b7-8344-49f6-98a2-e1891cbff803', modifydate=now() where ID = '18b6693f-2ca6-cb88-42b5-64cea7f18989';");
        $this->addSql("alter table iris_emailaccount rename column isuseimap TO fetch_protocol;");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount.fetch_protocol IS 'Протокол считывания писем';");
        $this->addSql("drop index iris_emailaccount_isuseimap_i;");
        $this->addSql("create index iris_emailaccount_fetch_protocol_i on iris_emailaccount using btree (fetch_protocol);");
        $this->addSql("update iris_emailaccount set fetch_protocol = 2 where fetch_protocol = 1;");
        $this->addSql("update iris_emailaccount set fetch_protocol = 1 where fetch_protocol = 0 or fetch_protocol is null;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("update iris_Table_Column set TableID='1fb8b784-40dd-4039-a616-c8485dfea753', Name='Использовать imap?', Code='isuseimap', IsDuplicate='0', DefaultValue=NULL, fkName=NULL, fkTableID=NULL, pkName=NULL, IndexName='iris_emailaccount_isuseimap_i', ColumnTypeID='687bc1a7-de12-ab78-11bd-6936d9a9ff75', isNotNull='0', OnDeleteID=NULL, OnUpdateID=NULL, Description=NULL, modifyid='005405b7-8344-49f6-98a2-e1891cbff803', modifydate=now() where ID = '18b6693f-2ca6-cb88-42b5-64cea7f18989';");
        $this->addSql("alter table iris_emailaccount rename column fetch_protocol TO isuseimap;");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount.isuseimap IS 'Использовать imap?';");
        $this->addSql("drop index iris_emailaccount_fetch_protocol_i;");
        $this->addSql("create index iris_emailaccount_isuseimap_i on iris_emailaccount using btree (isuseimap);");
        $this->addSql("update iris_emailaccount set isuseimap = 0 where isuseimap = 1;");
        $this->addSql("update iris_emailaccount set isuseimap = 1 where isuseimap = 2;");
    }
}
