<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Добавление поля iris_emailaccount.isdeletefromserver
 */
class Version20170427115813 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('3df99006-9abe-df66-6948-0c42d9274825', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '1fb8b784-40dd-4039-a616-c8485dfea753', 'Удалять с сервера при удалении?', 'isdeletefromserver', '0', NULL, NULL, NULL, NULL, 'iris_emailaccount_isdeletefromserver_i', '687bc1a7-de12-ab78-11bd-6936d9a9ff75', '0', NULL, NULL, NULL);");
        $this->addSql("alter table \"iris_emailaccount\" add \"isdeletefromserver\" smallint;");
        $this->addSql("comment on column \"iris_emailaccount\".\"isdeletefromserver\" is 'Удалять с сервера при удалении?';");
        $this->addSql("create index \"iris_emailaccount_isdeletefromserver_i\" on \"iris_emailaccount\" using btree (isdeletefromserver);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("delete from  iris_Table_Column  where id = '3df99006-9abe-df66-6948-0c42d9274825';");
        $this->addSql("alter table iris_emailaccount drop column isdeletefromserver;");
    }
}
