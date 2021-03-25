<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210325140222 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create all tables and data for Credit & Guaranty';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE credit_guaranty_field (id INT AUTO_INCREMENT NOT NULL, field_alias VARCHAR(100) NOT NULL, category VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, target_property_access_path VARCHAR(255) NOT NULL, comparable TINYINT(1) NOT NULL, predefined_items JSON DEFAULT NULL, unit VARCHAR(20) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_E65C1761B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program (id INT AUTO_INCREMENT NOT NULL, id_company_group_tag INT NOT NULL, id_current_status INT DEFAULT NULL, added_by INT NOT NULL, name VARCHAR(100) NOT NULL, description MEDIUMTEXT DEFAULT NULL, distribution_deadline DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', distribution_process JSON DEFAULT NULL, guaranty_duration SMALLINT DEFAULT NULL, guaranty_coverage NUMERIC(4, 4) DEFAULT NULL, rating_type VARCHAR(60) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', capped_at_amount NUMERIC(15, 2) DEFAULT NULL, capped_at_currency VARCHAR(3) DEFAULT NULL, funds_amount NUMERIC(15, 2) NOT NULL, funds_currency VARCHAR(3) NOT NULL, guaranty_cost_amount NUMERIC(15, 2) DEFAULT NULL, guaranty_cost_currency VARCHAR(3) DEFAULT NULL, UNIQUE INDEX UNIQ_190C774F5E237E06 (name), UNIQUE INDEX UNIQ_190C774FB5B48B91 (public_id), INDEX IDX_190C774F4237BD1D (id_company_group_tag), UNIQUE INDEX UNIQ_190C774F41AF0274 (id_current_status), INDEX IDX_190C774F699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_borrower_type_allocation (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, id_program_choice_option INT NOT NULL, max_allocation_rate NUMERIC(3, 2) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_5B4CC439B5B48B91 (public_id), INDEX IDX_5B4CC4394C70DEF4 (id_program), INDEX IDX_5B4CC439CB0F0BCB (id_program_choice_option), UNIQUE INDEX UNIQ_5B4CC4394C70DEF4CB0F0BCB (id_program, id_program_choice_option), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_choice_option (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, id_field INT NOT NULL, description VARCHAR(255) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_10BA4269B5B48B91 (public_id), INDEX IDX_10BA42694C70DEF4 (id_program), INDEX IDX_10BA4269B5700468 (id_field), UNIQUE INDEX UNIQ_10BA42696DE44026B57004684C70DEF4 (description, id_field, id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_contact (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, working_scope VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, phone VARCHAR(35) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_FB6A0C29B5B48B91 (public_id), INDEX IDX_FB6A0C294C70DEF4 (id_program), UNIQUE INDEX UNIQ_FB6A0C29E7927C744C70DEF4 (email, id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_eligibility (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, id_field INT NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E17147BBB5B48B91 (public_id), INDEX IDX_E17147BB4C70DEF4 (id_program), INDEX IDX_E17147BBB5700468 (id_field), UNIQUE INDEX UNIQ_E17147BBB57004684C70DEF4 (id_field, id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_eligibility_condition (id INT AUTO_INCREMENT NOT NULL, id_program_eligibility_configuration INT NOT NULL, id_left_operand_field INT NOT NULL, id_right_operand_field INT DEFAULT NULL, operation VARCHAR(10) NOT NULL, value_type VARCHAR(20) NOT NULL, value NUMERIC(15, 2) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F9820BF3B5B48B91 (public_id), INDEX IDX_F9820BF333C0C139 (id_program_eligibility_configuration), INDEX IDX_F9820BF34056F542 (id_left_operand_field), INDEX IDX_F9820BF324B37D48 (id_right_operand_field), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_eligibility_configuration (id INT AUTO_INCREMENT NOT NULL, id_program_eligibility INT NOT NULL, id_program_choice_option INT DEFAULT NULL, value VARCHAR(100) DEFAULT NULL, eligible TINYINT(1) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F485534DB5B48B91 (public_id), INDEX IDX_F485534DBA8D5FB3 (id_program_eligibility), INDEX IDX_F485534DCB0F0BCB (id_program_choice_option), UNIQUE INDEX UNIQ_F485534DBA8D5FB3CB0F0BCB (id_program_eligibility, id_program_choice_option), UNIQUE INDEX UNIQ_F485534DBA8D5FB31D775834 (id_program_eligibility, value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_grade_allocation (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, grade VARCHAR(10) NOT NULL, max_allocation_rate NUMERIC(3, 2) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_20B3F09AB5B48B91 (public_id), INDEX IDX_20B3F09A4C70DEF4 (id_program), UNIQUE INDEX UNIQ_20B3F09A4C70DEF4595AAE34 (id_program, grade), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_status (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, added_by INT NOT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_CEB64F62B5B48B91 (public_id), INDEX IDX_CEB64F624C70DEF4 (id_program), INDEX IDX_CEB64F62699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F4237BD1D FOREIGN KEY (id_company_group_tag) REFERENCES core_company_group_tag (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F41AF0274 FOREIGN KEY (id_current_status) REFERENCES credit_guaranty_program_status (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation ADD CONSTRAINT FK_5B4CC4394C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation ADD CONSTRAINT FK_5B4CC439CB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD CONSTRAINT FK_10BA42694C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD CONSTRAINT FK_10BA4269B5700468 FOREIGN KEY (id_field) REFERENCES credit_guaranty_field (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_contact ADD CONSTRAINT FK_FB6A0C294C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility ADD CONSTRAINT FK_E17147BB4C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility ADD CONSTRAINT FK_E17147BBB5700468 FOREIGN KEY (id_field) REFERENCES credit_guaranty_field (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD CONSTRAINT FK_F9820BF333C0C139 FOREIGN KEY (id_program_eligibility_configuration) REFERENCES credit_guaranty_program_eligibility_configuration (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD CONSTRAINT FK_F9820BF34056F542 FOREIGN KEY (id_left_operand_field) REFERENCES credit_guaranty_field (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition ADD CONSTRAINT FK_F9820BF324B37D48 FOREIGN KEY (id_right_operand_field) REFERENCES credit_guaranty_field (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration ADD CONSTRAINT FK_F485534DBA8D5FB3 FOREIGN KEY (id_program_eligibility) REFERENCES credit_guaranty_program_eligibility (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration ADD CONSTRAINT FK_F485534DCB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_program_grade_allocation ADD CONSTRAINT FK_20B3F09A4C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_status ADD CONSTRAINT FK_CEB64F624C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE credit_guaranty_program_status ADD CONSTRAINT FK_CEB64F62699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $fields = <<<INSERT_FIELDS
INSERT INTO credit_guaranty_field (public_id, field_alias, category, type, target_property_access_path, comparable, unit, predefined_items) VALUES
('77d246f8-8181-4d45-9a2a-268dd6795e70', 'juridical_person', 'general', 'bool', '', 0, NULL, NULL),
('3e2201f1-493f-475d-b84f-ee44e9065ea2', 'on_going_creation', 'general', 'bool', '', 0, NULL, NULL),
('a5ebc5fa-ebd6-450e-9c44-1aab84e65bbb', 'receiving_grant', 'general', 'bool', '', 0, NULL, NULL),
('0393c13d-1511-4d60-975e-ead448ed5d13', 'subsidiary', 'general', 'bool', '', 0, NULL, NULL),
('46c2d1b3-61fa-4d2f-a3f3-0336feecd2e2', 'borrower_type', 'profile', 'list', 'Unilend\\\CreditGuaranty\\\Entity\\\Borrower::type', 0, NULL, NULL),
('56d4b239-8b5a-41f0-9e65-4ced292b0c0c', 'company_name', 'profile', 'other', '', 0, NULL, NULL),
('bc84acbb-e1fe-4878-9e7b-7999c7a38282', 'company_address', 'profile', 'other', '', 0, NULL, NULL),
('4bd9fc81-aaaa-4753-913e-86c6b193fd85', 'borrower_identity', 'profile', 'other', '', 0, NULL, NULL),
('8f86ff38-1bfa-4608-a74d-7a40052d3f41', 'beneficiary_address', 'profile', 'other', '', 0, NULL, NULL),
('093a2142-ab5d-4b57-afb0-e8749131740b', 'tax_number', 'profile', 'other', '', 0, NULL, NULL),
('a2bf0ba4-9e56-43f2-94a5-5da47c80cadd', 'siren', 'activity', 'other', '', 0, NULL, NULL),
('f6ea8c30-48d1-4852-9c4a-5e1298f7f902', 'siret', 'activity', 'other', '', 0, NULL, NULL),
('932afe50-582a-462c-b5cc-16cdd3f09c07', 'activity_country', 'activity', 'list', '', 0, NULL, '["FR"]'),
('d61a4e71-4438-46f1-b1a5-376f98566c06', 'activity_start_date', 'activity', 'other', '', 0, NULL, NULL),
('6c067265-5ff5-49f4-84f0-e511a4a7d42e', 'employees_number', 'activity', 'other', '', 1, 'person', NULL),
('9865fb18-ba90-42ce-9455-e7c508acddd9', 'last_year_turnover', 'activity', 'other', '', 1, 'money', NULL),
('a2e2cbad-75d3-42a4-83d9-aef63da4360e', '5_years_average_turnover', 'activity', 'other', '', 1, 'money', NULL),
('938c689e-bddb-42b9-b84a-a00b18523e4f', 'total_assets', 'activity', 'other', '', 1, 'money', NULL),
('5e6b31a1-1007-4e8d-a1ce-9a38e62280b9', 'grant_amount', 'activity', 'other', '', 1, 'money', NULL),
('d5cbbd4c-0101-4d6b-b816-146a1943ee2e', 'low_density_medical_area_exercise', 'activity', 'other', '', 1, 'money', NULL),
('eef6e5ac-8de6-4084-a06b-dd2974141d94', 'legal_form', 'activity', 'list', '', 0, NULL, '["SARL","SAS","SASU","EURL","SA","SELAS"]');
INSERT_FIELDS;
        $this->addSql($fields);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option DROP FOREIGN KEY FK_10BA4269B5700468');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility DROP FOREIGN KEY FK_E17147BBB5700468');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition DROP FOREIGN KEY FK_F9820BF34056F542');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition DROP FOREIGN KEY FK_F9820BF324B37D48');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation DROP FOREIGN KEY FK_5B4CC4394C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option DROP FOREIGN KEY FK_10BA42694C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program_contact DROP FOREIGN KEY FK_FB6A0C294C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility DROP FOREIGN KEY FK_E17147BB4C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program_grade_allocation DROP FOREIGN KEY FK_20B3F09A4C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program_status DROP FOREIGN KEY FK_CEB64F624C70DEF4');
        $this->addSql('ALTER TABLE credit_guaranty_program_borrower_type_allocation DROP FOREIGN KEY FK_5B4CC439CB0F0BCB');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration DROP FOREIGN KEY FK_F485534DCB0F0BCB');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration DROP FOREIGN KEY FK_F485534DBA8D5FB3');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_condition DROP FOREIGN KEY FK_F9820BF333C0C139');
        $this->addSql('ALTER TABLE credit_guaranty_program DROP FOREIGN KEY FK_190C774F41AF0274');
        $this->addSql('DROP TABLE credit_guaranty_field');
        $this->addSql('DROP TABLE credit_guaranty_program');
        $this->addSql('DROP TABLE credit_guaranty_program_borrower_type_allocation');
        $this->addSql('DROP TABLE credit_guaranty_program_choice_option');
        $this->addSql('DROP TABLE credit_guaranty_program_contact');
        $this->addSql('DROP TABLE credit_guaranty_program_eligibility');
        $this->addSql('DROP TABLE credit_guaranty_program_eligibility_condition');
        $this->addSql('DROP TABLE credit_guaranty_program_eligibility_configuration');
        $this->addSql('DROP TABLE credit_guaranty_program_grade_allocation');
        $this->addSql('DROP TABLE credit_guaranty_program_status');
    }
}
