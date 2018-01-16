<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Добавление emailtypeid и issync в iris_emailaccount_mailbox
 */
class Version20180112113323 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // add iris_emailaccount_mailbox.emailtypeid
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('a5f7af10-a61f-3900-7db3-ae6a4280439e', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Тип письма', 'emailtypeid', '0', NULL, 'fk_iris_emailaccount_mailbox_emailtypeid', '4a757b2c-a7e5-4228-89a5-559c60b4c420', NULL, 'iris_emailaccount_mailbox_emailtypeid_i', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', '0', '9f8bccc8-923a-3e15-6484-f7f4168294b2', '9f8bccc8-923a-3e15-6484-f7f4168294b2', NULL);");
        $this->addSql("alter table \"iris_emailaccount_mailbox\" add \"emailtypeid\" character varying(36);");
        $this->addSql("comment on column \"iris_emailaccount_mailbox\".\"emailtypeid\" is 'Тип письма';");
        $this->addSql("alter table \"iris_emailaccount_mailbox\" add constraint fk_iris_emailaccount_mailbox_emailtypeid foreign key (emailtypeid) references iris_emailtype(id) match simple on update RESTRICT on delete RESTRICT;");
        $this->addSql("create index \"iris_emailaccount_mailbox_emailtypeid_i\" on \"iris_emailaccount_mailbox\" using btree (emailtypeid);");
        $this->addSql("update iris_emailaccount_mailbox set emailtypeid = (select id from iris_emailtype where code = 'Inbox');");

        // add iris_emailaccount_mailbox.issync
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('54f9e84d-a2bb-eb7e-2a15-d83808b7d065', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Синхронизировать?', 'issync', '0', NULL, NULL, NULL, NULL, 'iris_emailaccount_mailbox_issync_i', '687bc1a7-de12-ab78-11bd-6936d9a9ff75', '0', NULL, NULL, NULL);");
        $this->addSql("alter table \"iris_emailaccount_mailbox\" add \"issync\" smallint;;");
        $this->addSql("comment on column \"iris_emailaccount_mailbox\".\"issync\" is 'Синхронизировать?';;");
        $this->addSql("create index \"iris_emailaccount_mailbox_issync_i\" on \"iris_emailaccount_mailbox\" using btree (issync);");
        $this->addSql("update iris_emailaccount_mailbox set issync = 1;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // remove iris_emailaccount_mailbox.emailtypeid
        $this->addSql("delete from iris_Table_Column where id = 'a5f7af10-a61f-3900-7db3-ae6a4280439e';");
        $this->addSql("alter table iris_emailaccount_mailbox drop column emailtypeid;");

        // remove iris_emailaccount_mailbox.issync
        $this->addSql("delete from iris_Table_Column where id = '54f9e84d-a2bb-eb7e-2a15-d83808b7d065';");
        $this->addSql("alter table iris_emailaccount_mailbox drop column issync;");
    }
}
