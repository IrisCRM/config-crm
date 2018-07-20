<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180720160536 extends AbstractMigration
{

    public function getDescription()
    {
        return "Добавление поля iris_email.isanswered";
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('da87395c-f4c1-0aaa-47bd-940cf77fb13c', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '47d63606-5340-471a-a23c-b8e87c6e0a84', 'Отвеченное письмо (стрелочка)', 'isanswered', '0', 0, NULL, NULL, NULL, 'iris_email_isanswered_i', '687bc1a7-de12-ab78-11bd-6936d9a9ff75', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_email add isanswered smallint default 0;");
        $this->addSql("comment on column iris_email.isanswered is 'Отвеченное письмо (стрелочка)';");
        $this->addSql("create index iris_email_isanswered_i on iris_email using btree (isanswered);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("delete from iris_Table_Column where ID='da87395c-f4c1-0aaa-47bd-940cf77fb13c';");
        $this->addSql("alter table iris_email drop column isanswered;");
    }
}
