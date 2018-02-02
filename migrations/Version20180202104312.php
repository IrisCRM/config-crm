<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180202104312 extends AbstractMigration
{

    public function getDescription()
    {
        return "Замена полей cangtalk, gtalk на cantelegram, telegramchatid " .
            "в отчетах напоминаний; заполнение istelegram и telegramtext в iris_remind";
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("update iris_report_column set name = 'Получать уведомления по telegram', code = 'cantelegram', columnid = 'b9edb622-d5bd-4ec0-d85e-9a998907ffb0' where code = 'cangtalk';");
        $this->addSql("update iris_report_column set name = 'Telegram chat id', code = 'telegramchatid', columnid = 'c9af0a98-31fb-42ae-d304-baced3700b62' where code = 'gtalk';");
        $this->addSql("update iris_remind set telegramtext = gtalktext where gtalktext is not null;");
        $this->addSql("update iris_remind set istelegram = 1 where isgtalk = 1;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("update iris_report_column set name = 'Получать уведомления по google talk', code = 'cangtalk', columnid = '4b54f3b2-fa94-4837-859a-b057833c9bff' where code = 'cantelegram';");
        $this->addSql("update iris_report_column set name = 'Google account', code = 'gtalk', columnid = 'cc34269d-a972-6a28-1299-bb9ba118e70a' where code = 'telegramchatid';");
        $this->addSql("update iris_remind set telegramtext = null;");
        $this->addSql("update iris_remind set istelegram = null;");
    }
}
