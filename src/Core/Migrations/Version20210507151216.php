<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210507151216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3636 crate the tables for reservation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_guaranty_borrower (id INT AUTO_INCREMENT NOT NULL, id_borrower_type INT DEFAULT NULL, id_legal_form INT DEFAULT NULL, company_name VARCHAR(100) NOT NULL, grade VARCHAR(10) NOT NULL, tax_number VARCHAR(20) DEFAULT NULL, beneficiary_name VARCHAR(40) DEFAULT NULL, creation_in_progress TINYINT(1) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', address_road_name VARCHAR(100) DEFAULT NULL, address_road_number VARCHAR(10) DEFAULT NULL, address_city VARCHAR(30) DEFAULT NULL, address_post_code VARCHAR(10) DEFAULT NULL, address_country VARCHAR(30) DEFAULT NULL, UNIQUE INDEX UNIQ_D7ADB78CB5B48B91 (public_id), INDEX IDX_D7ADB78C7C357C47 (id_borrower_type), INDEX IDX_D7ADB78CDA79932F (id_legal_form), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_borrower_business_activity (id INT AUTO_INCREMENT NOT NULL, siret VARCHAR(14) DEFAULT NULL, employees_number SMALLINT DEFAULT NULL, subsidiary TINYINT(1) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', address_road_name VARCHAR(100) DEFAULT NULL, address_road_number VARCHAR(10) DEFAULT NULL, address_city VARCHAR(30) DEFAULT NULL, address_post_code VARCHAR(10) DEFAULT NULL, address_country VARCHAR(30) DEFAULT NULL, last_year_turnover_amount NUMERIC(15, 2) DEFAULT NULL, last_year_turnover_currency VARCHAR(3) DEFAULT NULL, five_years_average_turnover_amount NUMERIC(15, 2) DEFAULT NULL, five_years_average_turnover_currency VARCHAR(3) DEFAULT NULL, total_assets_amount NUMERIC(15, 2) DEFAULT NULL, total_assets_currency VARCHAR(3) DEFAULT NULL, grant_amount NUMERIC(15, 2) DEFAULT NULL, grant_currency VARCHAR(3) DEFAULT NULL, UNIQUE INDEX UNIQ_6008FDC0B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_financing_object (id INT AUTO_INCREMENT NOT NULL, id_reservation INT NOT NULL, id_financing_object INT NOT NULL, id_loan_type INT NOT NULL, duration SMALLINT NOT NULL, released_on_invoice TINYINT(1) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', money_amount NUMERIC(15, 2) NOT NULL, money_currency VARCHAR(3) NOT NULL, UNIQUE INDEX UNIQ_6AECF0F5B5B48B91 (public_id), INDEX IDX_6AECF0F55ADA84A2 (id_reservation), INDEX IDX_6AECF0F562547109 (id_financing_object), INDEX IDX_6AECF0F5DD46917A (id_loan_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_project (id INT AUTO_INCREMENT NOT NULL, id_program_choice_option INT NOT NULL, naf_code VARCHAR(5) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', funding_money_amount NUMERIC(15, 2) NOT NULL, funding_money_currency VARCHAR(3) NOT NULL, UNIQUE INDEX UNIQ_A452D025B5B48B91 (public_id), INDEX IDX_A452D025CB0F0BCB (id_program_choice_option), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_reservation (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, id_borrower INT NOT NULL, id_borrower_business_activity INT DEFAULT NULL, id_project INT DEFAULT NULL, id_current_status INT DEFAULT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_DE8BB857B5B48B91 (public_id), INDEX IDX_DE8BB8574C70DEF4 (id_program), UNIQUE INDEX UNIQ_DE8BB8578B4BA121 (id_borrower), UNIQUE INDEX UNIQ_DE8BB8572DAA06AE (id_borrower_business_activity), UNIQUE INDEX UNIQ_DE8BB857F12E799E (id_project), UNIQUE INDEX UNIQ_DE8BB85741AF0274 (id_current_status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_reservation_status (id INT AUTO_INCREMENT NOT NULL, id_reservation INT NOT NULL, added_by INT NOT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_92658B97B5B48B91 (public_id), INDEX IDX_92658B975ADA84A2 (id_reservation), INDEX IDX_92658B97699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78C7C357C47 FOREIGN KEY (id_borrower_type) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_borrower ADD CONSTRAINT FK_D7ADB78CDA79932F FOREIGN KEY (id_legal_form) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F55ADA84A2 FOREIGN KEY (id_reservation) REFERENCES credit_guaranty_reservation (id)');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F562547109 FOREIGN KEY (id_financing_object) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F5DD46917A FOREIGN KEY (id_loan_type) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D025CB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB8574C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB8578B4BA121 FOREIGN KEY (id_borrower) REFERENCES credit_guaranty_borrower (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB8572DAA06AE FOREIGN KEY (id_borrower_business_activity) REFERENCES credit_guaranty_borrower_business_activity (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB857F12E799E FOREIGN KEY (id_project) REFERENCES credit_guaranty_project (id)');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB85741AF0274 FOREIGN KEY (id_current_status) REFERENCES credit_guaranty_reservation_status (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_reservation_status ADD CONSTRAINT FK_92658B975ADA84A2 FOREIGN KEY (id_reservation) REFERENCES credit_guaranty_reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_reservation_status ADD CONSTRAINT FK_92658B97699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB8578B4BA121');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB8572DAA06AE');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB857F12E799E');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP FOREIGN KEY FK_6AECF0F55ADA84A2');
        $this->addSql('ALTER TABLE credit_guaranty_reservation_status DROP FOREIGN KEY FK_92658B975ADA84A2');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB85741AF0274');
        $this->addSql('DROP TABLE credit_guaranty_borrower');
        $this->addSql('DROP TABLE credit_guaranty_borrower_business_activity');
        $this->addSql('DROP TABLE credit_guaranty_financing_object');
        $this->addSql('DROP TABLE credit_guaranty_project');
        $this->addSql('DROP TABLE credit_guaranty_reservation');
        $this->addSql('DROP TABLE credit_guaranty_reservation_status');
    }
}
