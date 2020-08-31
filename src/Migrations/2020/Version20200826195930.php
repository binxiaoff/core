<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Ramsey\Uuid\Uuid;

final class Version20200826195930 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-2113 Remove Unifergie';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql("DELETE FROM project_participation WHERE id_company = (SELECT id FROM company WHERE short_code = 'UNFG')");
        $this->addSql("DELETE FROM project WHERE id_company_submitter = (SELECT id FROM company WHERE short_code = 'UNFG')");
        $this->addSql("UPDATE staff SET id_current_status = NULL WHERE id_company = (SELECT id FROM company WHERE short_code = 'UNFG')");
        $this->addSql("DELETE FROM staff_status WHERE id_staff IN (SELECT id FROM staff WHERE id_company = (SELECT id FROM company WHERE short_code = 'UNFG'))");
        $this->addSql("DELETE FROM staff WHERE id_company = (SELECT id FROM company WHERE short_code = 'UNFG')");
        $this->addSql("DELETE FROM company_module_log WHERE id_module IN (SELECT id FROM company_module WHERE id_company = (SELECT id FROM company WHERE short_code = 'UNFG'))");
        $this->addSql("DELETE FROM company_module WHERE id_company = (SELECT id FROM company WHERE short_code = 'UNFG')");
        $this->addSql("UPDATE company SET id_current_status = NULL WHERE short_code = 'UNFG'");
        $this->addSql("DELETE FROM company_status  WHERE id_company = (SELECT id FROM company WHERE short_code = 'UNFG')");
        $this->addSql("DELETE FROM company WHERE short_code = 'UNFG'");

        $this->addSql("UPDATE company SET display_name = 'CALF / Unifergie' WHERE display_name = 'Crédit agricole leasing'");
    }

    /**
     * @param Schema $schema
     *
     * @throws Exception
     */
    public function down(Schema $schema) : void
    {
        $companyUuid = '5846b3dd-3079-11ea-a36c-0226455cbcaf';
        $companyStatusUuid = Uuid::uuid4();
        $this->addSql(
            <<<SQL
INSERT INTO company (display_name, email_domain, siren, added, updated, short_code, public_id, group_name, vat_number, applicable_vat, bank_code, company_name) VALUES ('Unifergie', null, '326367620', NOW(), null, 'UNFG', '$companyUuid', null, null, 'metropolitan', '3', 'Unifergie');
SQL
);
        $this->addSql("INSERT INTO company_status (id_company, status, added, public_id) VALUES ((SELECT id FROM company WHERE public_id = '$companyUuid'), 10, NOW(), '$companyStatusUuid')");
        $this->addSql("UPDATE company SET id_current_status = (SELECT MAX(id) FROM company_status) WHERE public_id = '$companyUuid'");
        $this->addSql("UPDATE company SET display_name = 'Crédit agricole leasing' WHERE display_name = 'CALF / Unifergie'");
    }
}
