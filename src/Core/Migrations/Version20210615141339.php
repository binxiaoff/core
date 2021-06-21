<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210615141339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3882 CALS-3883 merge BorrowerBusinessActivity into Borrower';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB8572DAA06AE');
        $this->addSql('DROP TABLE credit_guaranty_borrower_business_activity');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD id_company_naf_code INT DEFAULT NULL, ADD id_exploitation_size INT DEFAULT NULL, ADD young_farmer TINYINT(1) DEFAULT NULL, ADD subsidiary TINYINT(1) DEFAULT NULL, ADD activity_start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD siret VARCHAR(14) DEFAULT NULL, ADD employees_number SMALLINT DEFAULT NULL, ADD address_department VARCHAR(30) DEFAULT NULL, ADD turnover_amount NUMERIC(15, 2) DEFAULT NULL, ADD turnover_currency VARCHAR(3) DEFAULT NULL, ADD total_assets_amount NUMERIC(15, 2) DEFAULT NULL, ADD total_assets_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78C442031D4 FOREIGN KEY (id_company_naf_code) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78C1E180518 FOREIGN KEY (id_exploitation_size) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_D7ADB78C442031D4 ON credit_guaranty_borrower (id_company_naf_code)');
        $this->addSql('CREATE INDEX IDX_D7ADB78C1E180518 ON credit_guaranty_borrower (id_exploitation_size)');
        $this->addSql('DROP INDEX UNIQ_DE8BB8572DAA06AE ON credit_guaranty_reservation');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP id_borrower_business_activity');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_borrower_business_activity (id INT AUTO_INCREMENT NOT NULL, id_naf_code INT DEFAULT NULL, siret VARCHAR(14) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, employees_number SMALLINT DEFAULT NULL, subsidiary TINYINT(1) DEFAULT NULL, public_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', address_road_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, address_road_number VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, address_city VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, address_post_code VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, address_country VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, last_year_turnover_amount NUMERIC(15, 2) DEFAULT NULL, last_year_turnover_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, five_years_average_turnover_amount NUMERIC(15, 2) DEFAULT NULL, five_years_average_turnover_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, total_assets_amount NUMERIC(15, 2) DEFAULT NULL, total_assets_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, grant_amount NUMERIC(15, 2) DEFAULT NULL, grant_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_6008FDC0EFE69DFD (id_naf_code), UNIQUE INDEX UNIQ_6008FDC0B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE credit_guaranty_borrower_business_activity ADD CONSTRAINT FK_6008FDC0EFE69DFD FOREIGN KEY (id_naf_code) REFERENCES credit_guaranty_program_choice_option (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP FOREIGN KEY FK_D7ADB78C442031D4');
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP FOREIGN KEY FK_D7ADB78C1E180518');
        $this->addSql('DROP INDEX IDX_D7ADB78C442031D4 ON credit_guaranty_borrower');
        $this->addSql('DROP INDEX IDX_D7ADB78C1E180518 ON credit_guaranty_borrower');
        $this->addSql('ALTER TABLE credit_guaranty_borrower DROP id_company_naf_code, DROP id_exploitation_size, DROP young_farmer, DROP subsidiary, DROP activity_start_date, DROP siret, DROP employees_number, DROP address_department, DROP turnover_amount, DROP turnover_currency, DROP total_assets_amount, DROP total_assets_currency');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD id_borrower_business_activity INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB8572DAA06AE FOREIGN KEY (id_borrower_business_activity) REFERENCES credit_guaranty_borrower_business_activity (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE8BB8572DAA06AE ON credit_guaranty_reservation (id_borrower_business_activity)');
    }
}
