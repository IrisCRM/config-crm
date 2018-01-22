<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180122142859 extends AbstractMigration
{
    public function getDescription()
    {
        return "Добавление полей \"Копия\" и \"Скрытая копия\" в таблицу \"Почта\"";
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // e_cc
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('a3049482-8a01-1e4a-67a9-a71cf9d79d72', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '47d63606-5340-471a-a23c-b8e87c6e0a84', 'Копия', 'e_cc', '0', NULL, NULL, NULL, NULL, NULL, '8a51f105-3368-0c76-68b7-26fa32f3c8b5', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_email add e_cc text;");
        $this->addSql("comment on column iris_email.e_cc is 'Копия';");

        // e_bcc
        $this->addSql("insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('113b0d66-a6cd-dbd7-2aad-6ad093906812', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '47d63606-5340-471a-a23c-b8e87c6e0a84', 'Скрытая копия', 'e_bcc', '0', NULL, NULL, NULL, NULL, NULL, '8a51f105-3368-0c76-68b7-26fa32f3c8b5', '0', NULL, NULL, NULL);");
        $this->addSql("alter table iris_email add e_bcc text;");
        $this->addSql("comment on column iris_email.e_bcc is 'Скрытая копия';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // e_cc
        $this->addSql("delete from iris_Table_Column where id = 'a3049482-8a01-1e4a-67a9-a71cf9d79d72';");
        $this->addSql("alter table iris_email drop column e_cc;");

        // e_bcc
        $this->addSql("delete from iris_Table_Column where id = '113b0d66-a6cd-dbd7-2aad-6ad093906812';");
        $this->addSql("alter table iris_email drop column e_bcc;");
    }
}
