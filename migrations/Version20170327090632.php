<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Удален неиспользуемый файл c логотипом
 */
class Version20170327090632 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("delete from iris_file_access where recordid = 'b9d92213-f50d-4806-a8b1-876079316897';");
        $this->addSql("delete from iris_file where id='b9d92213-f50d-4806-a8b1-876079316897';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("INSERT INTO iris_file (id, createid, createdate, modifyid, modifydate, ownerid, file_file, file_filename, date, filetypeid, filestateid, description, url, version, accountid, objectid, contactid, projectid, productid, issueid, bugid, incidentid, answerid, marketingid, offerid, invoiceid, pactid, documentid, isremind, reminddate, factinvoiceid, taskid, spaceid, paymentid, emailid, pollid) VALUES ('b9d92213-f50d-4806-a8b1-876079316897', '005405b7-8344-49f6-98a2-e1891cbff803', '2012-03-11 17:12:33.858', '005405b7-8344-49f6-98a2-e1891cbff803', '2012-03-11 17:12:33.858', '005405b7-8344-49f6-98a2-e1891cbff803', '25af9b63-52fb-43ce-9bd7-e958d27b1fcb', 'iriscrm.png', '2012-03-11 17:09:00', NULL, 'd8875ad5-9c34-1f57-946a-30d0f9cd2565', 'Логотип для ПФ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);");

        $this->addSql("INSERT INTO iris_file_access (id, createid, createdate, modifyid, modifydate, recordid, accessroleid, contactid, r, w, d, a) VALUES ('209cd15d-bb31-49d1-b665-26cccbbf64c9', NULL, NULL, NULL, NULL, 'b9d92213-f50d-4806-a8b1-876079316897', NULL, '005405b7-8344-49f6-98a2-e1891cbff803', '1', '1', '1', '1');");
        $this->addSql("INSERT INTO iris_file_access (id, createid, createdate, modifyid, modifydate, recordid, accessroleid, contactid, r, w, d, a) VALUES ('51dbfd2d-7d7f-462a-9b7a-6eab52680d33', NULL, NULL, NULL, NULL, 'b9d92213-f50d-4806-a8b1-876079316897', 'bb58b314-ecb3-4cb9-8af9-35b2192b84d9', NULL, '1', '1', '1', '1');");
        $this->addSql("INSERT INTO iris_file_access (id, createid, createdate, modifyid, modifydate, recordid, accessroleid, contactid, r, w, d, a) VALUES ('92e0cef5-178e-4ede-975a-d5821f3e52a0', NULL, NULL, NULL, NULL, 'b9d92213-f50d-4806-a8b1-876079316897', '9df3ee85-81cd-43f5-8851-94ae8d477b51', NULL, '1', '0', '0', '0');");
        $this->addSql("INSERT INTO iris_file_access (id, createid, createdate, modifyid, modifydate, recordid, accessroleid, contactid, r, w, d, a) VALUES ('3e0e0913-72f2-442a-aaad-f9908bba8f1d', NULL, NULL, NULL, NULL, 'b9d92213-f50d-4806-a8b1-876079316897', '2de8835d-1d81-4b5c-b529-32897036b823', NULL, '1', '0', '0', '0');");
    }
}
