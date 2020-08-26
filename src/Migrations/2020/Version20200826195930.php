<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
     */
    public function down(Schema $schema) : void
    {
        $this->addSql(
            <<<SQL
INSERT INTO company (id, id_parent_company, display_name, email_domain, siren, added, updated, short_code, public_id, id_current_status, group_name, vat_number, applicable_vat, bank_code, company_name) VALUES (NULL, 1, 'Unifergie', null, '326367620', '2019-08-14 15:43:41', null, 'UNFG', '5846b3dd-3079-11ea-a36c-0226455cbcaf', 2, null, null, 'metropolitan', '3', 'Unifergie');
SQL
);
        $this->addSql("UPDATE company SET display_name = 'Crédit agricole leasing' WHERE display_name = 'CALF / Unifergie'");
    }
}
