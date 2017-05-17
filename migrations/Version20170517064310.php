<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Заполнение поля iris_email.emailaccountid для входящих писем, загруженных по протоколу POP3
 */
class Version20170517064310 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("update iris_email as E
          set emailaccountid = EA.emailaccountid
          from iris_emailrecieved as EA
          where E.id = EA.emailid
            and E.emailtypeid = (select id from iris_emailtype where code = 'Inbox')
            and E.emailaccountid is null;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('SELECT 1');
    }
}
