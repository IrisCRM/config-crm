<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Удаление индексов с полей iris_email.e_from и iris_email.e_fro
 */
class Version20180117105044 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // e_from
        $this->addSql("update iris_Table_Column set IndexName = null where ID = '91329034-1fca-a7e9-1071-dcb179f6406d';");
        $this->addSql("DROP INDEX public.iris_email_fk_i7;");

        // e_fro
        $this->addSql("update iris_Table_Column set IndexName = null where ID = '90b93fb3-b5d3-4654-d719-331c3713ef57';");
        $this->addSql("DROP INDEX public.iris_email_fk_i8;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // e_from
        $this->addSql("update iris_Table_Column set IndexName = 'iris_email_fk_i7' where ID = '91329034-1fca-a7e9-1071-dcb179f6406d';");
        $this->addSql("CREATE INDEX iris_email_fk_i7 ON public.iris_email USING btree (e_from COLLATE pg_catalog.\"default\");");

        // e_fro
        $this->addSql("update iris_Table_Column set IndexName = 'iris_email_fk_i8' where ID = '90b93fb3-b5d3-4654-d719-331c3713ef57';");
        $this->addSql("CREATE INDEX iris_email_fk_i8 ON public.iris_email USING btree (e_to COLLATE pg_catalog.\"default\");");

    }
}
