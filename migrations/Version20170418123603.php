<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Изменение таблицы "Почтовые аккаунты" (поля imap), добавлена таблица "Папки почтового аккаунта"
 */
class Version20170418123603 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // add iris_emailaccount.isuseimap
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('18b6693f-2ca6-cb88-42b5-64cea7f18989', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '1fb8b784-40dd-4039-a616-c8485dfea753', 'Использовать imap?', 'isuseimap', '0', NULL, NULL, NULL, NULL, 'iris_emailaccount_isuseimap_i', '687bc1a7-de12-ab78-11bd-6936d9a9ff75', '0', NULL, NULL, NULL);");
        $this->addSql("alter table \"iris_emailaccount\" add \"isuseimap\" smallint;");
        $this->addSql("comment on column \"iris_emailaccount\".\"isuseimap\" is 'Использовать imap?';");
        $this->addSql("create index \"iris_emailaccount_isuseimap_i\" on \"iris_emailaccount\" using btree (isuseimap);");
        $this->addSql("update iris_emailaccount set isuseimap = 0;");

        // add iris_emailaccount.sentmailboxname
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('6a027e3e-05bf-c48d-e722-83acda074f2c', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '1fb8b784-40dd-4039-a616-c8485dfea753', 'Папка для отправленных писем', 'sentmailboxname', '0', NULL, NULL, NULL, NULL, 'iris_emailaccount_sentmailboxname_i', '332cb042-111b-3598-4458-7b36a1d0b67f', '0', NULL, NULL, NULL);");
        $this->addSql("alter table \"iris_emailaccount\" add \"sentmailboxname\" character varying(250);");
        $this->addSql("comment on column \"iris_emailaccount\".\"sentmailboxname\" is 'Папка для отправленных писем';");
        $this->addSql("create index \"iris_emailaccount_sentmailboxname_i\" on \"iris_emailaccount\" using btree (sentmailboxname);");

        // add iris_emailaccount_mailbox
        $this->addSql("insert into iris_Table (ID, createid, createdate, modifyid, modifydate, Code, Name, is_access, islog, SectionID, ShowColumnID, Dictionary, DictionaryGroupID, Detail, Description) values ('87149fd5-3178-3264-eba6-f14193db6506', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'iris_emailaccount_mailbox', 'Папки почтового аккаунта', '0', '0', NULL, NULL, NULL, NULL, NULL, NULL);");
        $this->addSql("create table \"iris_emailaccount_mailbox\" (id character varying(36) NOT NULL,createid character varying(36),createdate timestamp without time zone,modifyid character varying(36),modifydate timestamp without time zone,\"name\" character varying(250) NOT NULL,code character varying(250),description character varying(1000),orderpos character varying(30),CONSTRAINT pk_iris_emailaccount_mailbox PRIMARY KEY (id));");
        $this->addSql("COMMENT ON TABLE iris_emailaccount_mailbox IS 'Папки почтового аккаунта';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.id IS 'ID';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.createid IS 'Автор';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.createdate IS 'Дата создания';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.modifyid IS 'Изменил';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.modifydate IS 'Дата изменения';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.\"name\" IS 'Название';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.code IS 'Код';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.description IS 'Описание';");
        $this->addSql("COMMENT ON COLUMN iris_emailaccount_mailbox.orderpos IS 'Позиция сортировки';");
        $this->addSql("CREATE UNIQUE INDEX iris_emailaccount_mailbox_pk_i ON iris_emailaccount_mailbox USING btree(id);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull, indexname, pkname) values ('3b8845a9-f897-6c2e-415b-fd29b5072274', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'ID', 'id', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', 1, 'iris_emailaccount_mailbox_pk_i', 'pk_iris_emailaccount_mailbox');");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull) values ('f60a541b-c5b5-af10-617e-e696c9cf0d88', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Автор', 'createid', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull) values ('f8c82fe4-6f9e-ee17-c5ca-95ef2ae6e86a', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Дата создания', 'createdate', '666d5a4e-6064-9286-a921-e7957d39d283', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull) values ('e8af32ee-6fc0-4237-4685-7cdc1a15e35e', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Изменил', 'modifyid', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull) values ('9cf27577-b608-e632-5de0-fdcae246b298', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Дата изменения', 'modifydate', '666d5a4e-6064-9286-a921-e7957d39d283', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull) values ('120b6d69-9384-8904-5fdc-81b1b6238608', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Название', 'name', '332cb042-111b-3598-4458-7b36a1d0b67f', 1);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull) values ('0f7b7c58-be50-703c-b112-f3440e5c1b46', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Код', 'code', '332cb042-111b-3598-4458-7b36a1d0b67f', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull) values ('0133291e-3d59-fbd8-473c-8c87ac55c138', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Описание', 'description', '8e1d85be-6230-4c6f-6905-1aa87d25fa98', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, \"name\", code, columntypeid, isnotnull) values ('5bd6993e-e40d-ab5b-334e-02ccce837500', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Позиция сортировки', 'orderpos', '45fd9416-c707-ba1f-66c6-a6bf69424474', 0);");
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('426989f6-9933-1a97-2386-2eb17ab72c5e', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Имя для отображения', 'displayname', '0', NULL, NULL, NULL, NULL, 'iris_emailaccount_mailbox_displayname_i', '332cb042-111b-3598-4458-7b36a1d0b67f', '0', NULL, NULL, NULL);");
        $this->addSql("alter table \"iris_emailaccount_mailbox\" add \"displayname\" character varying(250);");
        $this->addSql("comment on column \"iris_emailaccount_mailbox\".\"displayname\" is 'Имя для отображения';");
        $this->addSql("create index \"iris_emailaccount_mailbox_displayname_i\" on \"iris_emailaccount_mailbox\" using btree (displayname);");
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('d117a12f-f3f5-33a0-0646-1bf3ccd6bded', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Последний uid', 'lastuid', '0', NULL, NULL, NULL, NULL, 'iris_emailaccount_mailbox_lastuid_i', '21622686-9ef9-b601-eb81-18b5aa8634b5', '0', NULL, NULL, NULL);");
        $this->addSql("alter table \"iris_emailaccount_mailbox\" add \"lastuid\" integer;");
        $this->addSql("comment on column \"iris_emailaccount_mailbox\".\"lastuid\" is 'Последний uid';");
        $this->addSql("create index \"iris_emailaccount_mailbox_lastuid_i\" on \"iris_emailaccount_mailbox\" using btree (lastuid);");
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('49c02fbd-45a7-b1c8-6bfc-f2d7c7aee69f', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'Почтовый аккаунт', 'emailaccountid', '0', NULL, 'fk_iris_emailaccount_mailbox_emailaccountid', '1fb8b784-40dd-4039-a616-c8485dfea753', NULL, 'iris_emailaccount_mailbox_emailaccountid_i', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', '1', '9f8bccc8-923a-3e15-6484-f7f4168294b2', '9f8bccc8-923a-3e15-6484-f7f4168294b2', NULL);");
        $this->addSql("alter table \"iris_emailaccount_mailbox\" add \"emailaccountid\" character varying(36)");
        $this->addSql("comment on column \"iris_emailaccount_mailbox\".\"emailaccountid\" is 'Почтовый аккаунт'");
        $this->addSql("alter table \"iris_emailaccount_mailbox\" alter column \"emailaccountid\" set not null");
        $this->addSql("alter table \"iris_emailaccount_mailbox\" add constraint fk_iris_emailaccount_mailbox_emailaccountid foreign key (emailaccountid) references iris_emailaccount(id) match simple on update RESTRICT on delete RESTRICT;");
        $this->addSql("create index \"iris_emailaccount_mailbox_emailaccountid_i\" on \"iris_emailaccount_mailbox\" using btree (emailaccountid);");
        $this->addSql("insert into iris_Table_TableGroup (ID, createid, createdate, modifyid, modifydate, TableID, TableGroupID) values ('0f584a40-082b-a022-6761-c56e902573c6', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '87149fd5-3178-3264-eba6-f14193db6506', 'f1f7a6f6-0acc-e37c-616f-2758b4757cf2');");

        // add iris_email.uid
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('a41bfb5a-fe61-5796-29e4-2af6c1741823', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '47d63606-5340-471a-a23c-b8e87c6e0a84', 'uid', 'uid', '0', NULL, NULL, NULL, NULL, 'iris_email_uid_i', '21622686-9ef9-b601-eb81-18b5aa8634b5', '0', NULL, NULL, NULL);");
        $this->addSql("alter table \"iris_email\" add \"uid\" integer");
        $this->addSql("comment on column \"iris_email\".\"uid\" is 'uid'");
        $this->addSql("create index \"iris_email_uid_i\" on \"iris_email\" using btree (uid);");

        // add iris_email.mailboxid
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('549ad278-626a-d3be-ae6f-ff15731a21f5', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '47d63606-5340-471a-a23c-b8e87c6e0a84', 'Почтовая папка', 'mailboxid', '0', NULL, 'fk_iris_email_mailboxid', '87149fd5-3178-3264-eba6-f14193db6506', NULL, 'iris_email_mailboxid_i', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', '0', '9f8bccc8-923a-3e15-6484-f7f4168294b2', '9f8bccc8-923a-3e15-6484-f7f4168294b2', NULL);");
        $this->addSql("alter table \"iris_email\" add \"mailboxid\" character varying(36)");
        $this->addSql("comment on column \"iris_email\".\"mailboxid\" is 'Почтовая папка'");
        $this->addSql("alter table \"iris_email\" add constraint fk_iris_email_mailboxid foreign key (mailboxid) references iris_emailaccount_mailbox(id) match simple on update RESTRICT on delete RESTRICT;");
        $this->addSql("create index \"iris_email_mailboxid_i\" on \"iris_email\" using btree (mailboxid);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // remove iris_emailaccount.isuseimap
        $this->addSql("delete from iris_Table_Column where id = '18b6693f-2ca6-cb88-42b5-64cea7f18989';");
        $this->addSql("alter table \"iris_emailaccount\" drop column \"isuseimap\";");

        // remove iris_emailaccount.sentmailboxname
        $this->addSql("delete from iris_Table_Column where id = '6a027e3e-05bf-c48d-e722-83acda074f2c';");
        $this->addSql("alter table \"iris_emailaccount\" drop column \"sentmailboxname\";");

        // remove iris_email.uid
        $this->addSql("alter table iris_email drop column uid;");
        $this->addSql("delete from iris_table_column where id = 'a41bfb5a-fe61-5796-29e4-2af6c1741823';");
        // remove iris_email.mailboxid
        $this->addSql("alter table iris_email drop column mailboxid;");
        $this->addSql("delete from iris_table_column where id = '549ad278-626a-d3be-ae6f-ff15731a21f5';");

        // remove iris_emailaccount_mailbox
        $this->addSql("drop table iris_emailaccount_mailbox;");
        $this->addSql("delete from iris_Table_TableGroup where id = '0f584a40-082b-a022-6761-c56e902573c6'");
        $this->addSql("delete from iris_table_column where id = '3b8845a9-f897-6c2e-415b-fd29b5072274';");
        $this->addSql("delete from iris_table_column where id = 'f60a541b-c5b5-af10-617e-e696c9cf0d88';");
        $this->addSql("delete from iris_table_column where id = 'f8c82fe4-6f9e-ee17-c5ca-95ef2ae6e86a';");
        $this->addSql("delete from iris_table_column where id = 'e8af32ee-6fc0-4237-4685-7cdc1a15e35e';");
        $this->addSql("delete from iris_table_column where id = '9cf27577-b608-e632-5de0-fdcae246b298';");
        $this->addSql("delete from iris_table_column where id = '120b6d69-9384-8904-5fdc-81b1b6238608';");
        $this->addSql("delete from iris_table_column where id = '0f7b7c58-be50-703c-b112-f3440e5c1b46';");
        $this->addSql("delete from iris_table_column where id = '0133291e-3d59-fbd8-473c-8c87ac55c138';");
        $this->addSql("delete from iris_table_column where id = '5bd6993e-e40d-ab5b-334e-02ccce837500';");
        $this->addSql("delete from iris_table_column where id = '426989f6-9933-1a97-2386-2eb17ab72c5e';");
        $this->addSql("delete from iris_table_column where id = 'd117a12f-f3f5-33a0-0646-1bf3ccd6bded';");
        $this->addSql("delete from iris_table_column where id = '49c02fbd-45a7-b1c8-6bfc-f2d7c7aee69f';");

        $this->addSql("delete from iris_Table where id = '87149fd5-3178-3264-eba6-f14193db6506';");
    }
}
