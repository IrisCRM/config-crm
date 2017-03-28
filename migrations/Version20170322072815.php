<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Для печатных форм личного кабинета указать раздел "Мои счета"
 */
class Version20170322072815 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // ПФ "Счет из ЛК" к разделу "Мои счета"
        $this->addSql("update iris_printform set sectionid = 'e5614f1d-7e28-48fd-b1d9-029cfe0ebf72' where id = '5b8910d8-6b7e-652a-7a4e-e7e2e67db573';");
        // ПФ "Квитанция из ЛК" к разделу "Мои счета"
        $this->addSql("update iris_printform set sectionid = 'e5614f1d-7e28-48fd-b1d9-029cfe0ebf72' where id = 'f9221f7f-b63d-2fde-daf8-bd77dd8aa022';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // ПФ "Счет из ЛК" к разделу "Счета"
        $this->addSql("update iris_printform set sectionid = 'cf43df83-3e7b-336c-a0ab-2caecdfbb8fc' where id = '5b8910d8-6b7e-652a-7a4e-e7e2e67db573';");
        // ПФ "Квитанция из ЛК" к разделу "Счета"
        $this->addSql("update iris_printform set sectionid = 'cf43df83-3e7b-336c-a0ab-2caecdfbb8fc' where id = 'f9221f7f-b63d-2fde-daf8-bd77dd8aa022';");
    }
}
