<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180412081854 extends AbstractMigration
{

    public function getDescription()
    {
        return "Добавление таблицы  iris_contact_token";
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // iris_contact_token
        $this->addSql("insert into iris_Table (ID, createid, createdate, modifyid, modifydate, Code, Name, is_access, islog, SectionID, ShowColumnID, Dictionary, DictionaryGroupID, Detail, Description) values ('de465cc5-4d22-641f-e959-7be58a8eb5fc', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'iris_contact_token', 'Токены контактов', '0', '0', NULL, NULL, NULL, NULL, NULL, NULL);");
        $this->addSql("create table iris_contact_token (id character varying(36) NOT NULL,createid character varying(36),createdate timestamp without time zone,modifyid character varying(36),modifydate timestamp without time zone,name character varying(250) NOT NULL,code character varying(250),description character varying(1000),CONSTRAINT pk_iris_contact_token PRIMARY KEY (id));");
        $this->addSql("COMMENT ON TABLE iris_contact_token IS 'Токены контактов';");
        $this->addSql("COMMENT ON COLUMN iris_contact_token.id IS 'ID';");
        $this->addSql("COMMENT ON COLUMN iris_contact_token.createid IS 'Автор';");
        $this->addSql("COMMENT ON COLUMN iris_contact_token.createdate IS 'Дата создания';");
        $this->addSql("COMMENT ON COLUMN iris_contact_token.modifyid IS 'Изменил';");
        $this->addSql("COMMENT ON COLUMN iris_contact_token.modifydate IS 'Дата изменения';");
        $this->addSql("COMMENT ON COLUMN iris_contact_token.name IS 'Название';");
        $this->addSql("COMMENT ON COLUMN iris_contact_token.code IS 'Код';");
        $this->addSql("COMMENT ON COLUMN iris_contact_token.description IS 'Описание';");
        $this->addSql("CREATE UNIQUE INDEX iris_contact_token_pk_i ON iris_contact_token USING btree(id);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, name, code, columntypeid, isnotnull, indexname, pkname) values ('85ec964f-9864-4d14-379c-775210ba6749', '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'ID', 'id', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', 1, 'iris_contact_token_pk_i', 'pk_iris_contact_token');");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, name, code, columntypeid, isnotnull) values ('b0fb4030-3098-3ae5-42a1-b812ad60c56e', '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Автор', 'createid', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, name, code, columntypeid, isnotnull) values ('304e663f-2006-3842-0814-4a7c02694305', '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Дата создания', 'createdate', '666d5a4e-6064-9286-a921-e7957d39d283', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, name, code, columntypeid, isnotnull) values ('c40fa875-f708-1309-b5e2-3e315cb1ddab', '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Изменил', 'modifyid', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, name, code, columntypeid, isnotnull) values ('8efb5a9b-c221-2fd5-9984-a196c18f69d4', '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Дата изменения', 'modifydate', '666d5a4e-6064-9286-a921-e7957d39d283', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, name, code, columntypeid, isnotnull) values ('701b888e-a703-1728-12a1-a7112acca15d', '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Название', 'name', '332cb042-111b-3598-4458-7b36a1d0b67f', 1);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, name, code, columntypeid, isnotnull) values ('781fe99d-df38-6198-a608-985f6c152695', '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Код', 'code', '332cb042-111b-3598-4458-7b36a1d0b67f', 0);");
        $this->addSql("insert into iris_table_column (id, createid, createdate, tableid, name, code, columntypeid, isnotnull) values ('7d2d9e7b-f7df-01eb-43e5-f21b35ac1c63', '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Описание', 'description', '8e1d85be-6230-4c6f-6905-1aa87d25fa98', 0);");
        // contactid
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('4431e8ee-bad0-3c11-09d0-4d615ed7739d', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Контакт', 'contactid', '0', NULL, 'fk_iris_contact_token_contactid', '58841eee-99d0-373f-b905-4031fef6c501', NULL, 'iris_contact_token_contactid_i', '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', '1', '9f8bccc8-923a-3e15-6484-f7f4168294b2', '9f8bccc8-923a-3e15-6484-f7f4168294b2', NULL);");
        $this->addSql("alter table iris_contact_token add contactid character varying(36);");
        $this->addSql("comment on column iris_contact_token.contactid is 'Контакт';");
        $this->addSql("alter table iris_contact_token alter column contactid set not null;");
        $this->addSql("alter table iris_contact_token add constraint fk_iris_contact_token_contactid foreign key (contactid) references iris_contact(id) match simple on update RESTRICT on delete RESTRICT;");
        $this->addSql("create index iris_contact_token_contactid_i on iris_contact_token using btree (contactid);");
        // expired_date
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('c796b226-2b42-2d6a-428e-0f5eafb83982', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Дата окончания', 'expired_date', '0', NULL, NULL, NULL, NULL, 'iris_contact_token_expired_date_i', '666d5a4e-6064-9286-a921-e7957d39d283', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_contact_token add expired_date timestamp without time zone;");
        $this->addSql("comment on column iris_contact_token.expired_date is 'Дата окончания';");
        $this->addSql("create index iris_contact_token_expired_date_i on iris_contact_token using btree (expired_date);");
        // isactive
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('0e99bc1e-c2bc-0e0e-1c37-b57684f14a87', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'de465cc5-4d22-641f-e959-7be58a8eb5fc', 'Активен?', 'isactive', '0', '1', NULL, NULL, NULL, 'iris_contact_token_isactive_i', '21622686-9ef9-b601-eb81-18b5aa8634b5', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_contact_token add isactive integer;");
        $this->addSql("comment on column iris_contact_token.isactive is 'Активен?';");
        $this->addSql("alter table iris_contact_token alter column isactive set default 1;");
        $this->addSql("create index iris_contact_token_isactive_i on iris_contact_token using btree (isactive);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("drop table iris_contact_token");
        $this->addSql("delete from iris_table where id='de465cc5-4d22-641f-e959-7be58a8eb5fc'");
    }
}
