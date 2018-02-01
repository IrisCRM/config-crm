<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180201085329 extends AbstractMigration
{

    public function getDescription()
    {
        return "Добавление системных параметров telegram_bot_username,
         telegram_bot_api_key а также поля iris_contact.istelegramnotify, iris_contact.telegramchatid";
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // telegram_bot_username
        $this->addSql("insert into iris_SystemVariable (ID, createid, createdate, modifyid, modifydate, Name, Code, StringValue, IntValue, FloatValue, DateValue, GUIDValue, VariableTypeID, Description) values ('ab2dd25d-dabb-5dab-ffdf-c36d59db361f', '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), 'Telegram (имя бота) для отправки уведомлений', 'telegram_bot_username', '<укажите_имя>', NULL, '0', NULL, NULL, 'ef4d2122-c9cb-469c-9d5b-dd628ad86f03', 'Имя (username) telegram бота. Имя бота должно заканчиваться на \"bot\" или \"_bot\"');");
        
        // telegram_bot_api_key
        $this->addSql("insert into iris_SystemVariable (ID, createid, createdate, modifyid, modifydate, Name, Code, StringValue, IntValue, FloatValue, DateValue, GUIDValue, VariableTypeID, Description) values ('8ae9630c-b467-3937-aeb7-f79503ddd2b4', '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), 'Telegram (API токен) для отправки уведомлений', 'telegram_bot_api_key', '<укажите_токен>', NULL, '0', NULL, NULL, 'ef4d2122-c9cb-469c-9d5b-dd628ad86f03', 'Как создать бота и получить токен: https://core.telegram.org/bots#6-botfather');");

        // iris_contact.telegramchatid
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('c9af0a98-31fb-42ae-d304-baced3700b62', '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), '58841eee-99d0-373f-b905-4031fef6c501', 'Telegram chat_id', 'telegramchatid', '0', NULL, NULL, NULL, NULL, 'iris_contact_telegramchatid_i', '21622686-9ef9-b601-eb81-18b5aa8634b5', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_contact add telegramchatid integer;");
        $this->addSql("comment on column iris_contact.telegramchatid is 'Telegram chat_id';");
        $this->addSql("create index iris_contact_telegramchatid_i on iris_contact using btree (telegramchatid);");

        // iris_contact.istelegramnotify
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('b9edb622-d5bd-4ec0-d85e-9a998907ffb0', '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), '706bd811-ac2a-84e6-ee30-8d4865cd675d', now(), '58841eee-99d0-373f-b905-4031fef6c501', 'Получать уведомления по telegram', 'istelegramnotify', '0', NULL, NULL, NULL, NULL, 'iris_contact_istelegramnotify_i', '21622686-9ef9-b601-eb81-18b5aa8634b5', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_contact add istelegramnotify integer;");
        $this->addSql("comment on column iris_contact.istelegramnotify is 'Получать уведомления по telegram';");
        $this->addSql("create index iris_contact_istelegramnotify_i on iris_contact using btree (istelegramnotify);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // telegram_bot_username
        $this->addSql("delete from iris_SystemVariable where ID='ab2dd25d-dabb-5dab-ffdf-c36d59db361f';");
        // telegram_bot_api_key
        $this->addSql("delete from iris_SystemVariable where ID='8ae9630c-b467-3937-aeb7-f79503ddd2b4';");

        // iris_contact.telegramchatid
        $this->addSql("delete from iris_Table_Column where id = 'c9af0a98-31fb-42ae-d304-baced3700b62';");
        $this->addSql("alter table iris_contact drop column telegramchatid;");

        // iris_contact.istelegramnotify
        $this->addSql("delete from iris_Table_Column where id = 'b9edb622-d5bd-4ec0-d85e-9a998907ffb0';");
        $this->addSql("alter table iris_contact drop column istelegramnotify;");
    }
}
