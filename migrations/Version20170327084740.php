<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Замена функции "Картинка" на "КартинкаПоСсылке" в печатных формах
 */
class Version20170327084740 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("update iris_printform set printformtext = replace(printformtext, '{Картинка({ФайлПоОписанию(Логотип для ПФ)})}', '{КартинкаПоСсылке(img/logo.png)}')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("update iris_printform set printformtext = replace(printformtext, '{КартинкаПоСсылке(img/logo.png)}', '{Картинка({ФайлПоОписанию(Логотип для ПФ)})}')");
    }
}
