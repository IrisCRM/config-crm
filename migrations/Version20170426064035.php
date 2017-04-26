<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Добавление поля iris_emailaccount.ownerid
 */
class Version20170426064035 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('f8d5aab9-e203-5aa3-d662-e4d2847ee698', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '1fb8b784-40dd-4039-a616-c8485dfea753', 'Ответственный', 'ownerid', '0', NULL, 'fk_iris_emailaccount_ownerid', '58841eee-99d0-373f-b905-4031fef6c501', NULL, 'iris_emailaccount_ownerid_i', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', '0', '9f8bccc8-923a-3e15-6484-f7f4168294b2', '9f8bccc8-923a-3e15-6484-f7f4168294b2', NULL);");
        $this->addSql("alter table \"iris_emailaccount\" add \"ownerid\" character varying(36);");
        $this->addSql("comment on column \"iris_emailaccount\".\"ownerid\" is 'Ответственный';");
        $this->addSql("alter table \"iris_emailaccount\" add constraint fk_iris_emailaccount_ownerid foreign key (ownerid) references iris_contact(id) match simple on update RESTRICT on delete RESTRICT;");
        $this->addSql("create index \"iris_emailaccount_ownerid_i\" on \"iris_emailaccount\" using btree (ownerid);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("delete from iris_Table_Column where id = 'f8d5aab9-e203-5aa3-d662-e4d2847ee698';");
        $this->addSql("alter table iris_emailaccount drop column ownerid;");
    }
}
