<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180201150855 extends AbstractMigration
{
    public function getDescription()
    {
        return "Добавление istelegram, telegramtext в таблицу iris_remind";
    }


    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // istelegram
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('4e79a859-c89e-a941-8aac-ea1df0b07faa', '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), 'fde0e85f-ad63-48ad-84e8-63c2af8e1318', 'Telegram уведомление', 'istelegram', '0', NULL, NULL, NULL, NULL, 'iris_remind_istelegram_i', '687bc1a7-de12-ab78-11bd-6936d9a9ff75', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_remind add istelegram smallint;");
        $this->addSql("comment on column iris_remind.istelegram is 'Telegram уведомление';");
        $this->addSql("create index iris_remind_istelegram_i on iris_remind using btree (istelegram);");

        // telegramtext
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('060c4ef0-9387-718a-505a-c8335c4fdf69', '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), 'fde0e85f-ad63-48ad-84e8-63c2af8e1318', 'Текст для telegram уведомления', 'telegramtext', '0', NULL, NULL, NULL, NULL, 'iris_remind_telegramtext_i', '8e1d85be-6230-4c6f-6905-1aa87d25fa98', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_remind add telegramtext character varying(1000);");
        $this->addSql("comment on column iris_remind.telegramtext is 'Текст для telegram уведомления';");
        $this->addSql("create index iris_remind_telegramtext_i on iris_remind using btree (telegramtext);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // istelegram
        $this->addSql("delete from iris_Table_Column where ID='4e79a859-c89e-a941-8aac-ea1df0b07faa';");
        $this->addSql("alter table iris_remind drop column istelegram;");

        // telegramtext
        $this->addSql("delete from iris_Table_Column where ID='060c4ef0-9387-718a-505a-c8335c4fdf69';");
        $this->addSql("alter table iris_remind drop column telegramtext;");
    }
}
