<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180720161845 extends AbstractMigration
{

    public function getDescription()
    {
        return "Вычисление значений для поля iris_email.isanswered";
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("update iris_email T0 set isanswered = (case when exists (select 1 from iris_email E where E.parentemailid = T0.id) then 1 else 0 end);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("update iris_email set isanswered = 0;");
    }
}
