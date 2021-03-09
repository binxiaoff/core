<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210303111428 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3257 create the tables related to the eligibility';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE credit_guaranty_eligibility_criteria (id INT AUTO_INCREMENT NOT NULL, field_alias VARCHAR(100) NOT NULL, category VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, target_property_access_path VARCHAR(255) NOT NULL, comparable TINYINT(1) NOT NULL, unit VARCHAR(20) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_F91B38B0B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_choice_option (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, description VARCHAR(255) NOT NULL, field_alias VARCHAR(100) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_10BA4269B5B48B91 (public_id), INDEX IDX_10BA42694C70DEF4 (id_program), UNIQUE INDEX UNIQ_10BA42696DE44026CACBFD6F4C70DEF4 (description, field_alias, id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_eligibility (id INT AUTO_INCREMENT NOT NULL, id_program INT NOT NULL, id_eligibility_criteria INT NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E17147BBB5B48B91 (public_id), INDEX IDX_E17147BB4C70DEF4 (id_program), INDEX IDX_E17147BBF79B4C9A (id_eligibility_criteria), UNIQUE INDEX UNIQ_E17147BBF79B4C9A4C70DEF4 (id_eligibility_criteria, id_program), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE credit_guaranty_program_eligibility_configuration (id INT AUTO_INCREMENT NOT NULL, id_program_eligibility INT NOT NULL, id_program_choice_option INT DEFAULT NULL, value VARCHAR(100) DEFAULT NULL, eligible TINYINT(1) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F485534DB5B48B91 (public_id), INDEX IDX_F485534DBA8D5FB3 (id_program_eligibility), INDEX IDX_F485534DCB0F0BCB (id_program_choice_option), UNIQUE INDEX UNIQ_F485534DBA8D5FB3CB0F0BCB (id_program_eligibility, id_program_choice_option), UNIQUE INDEX UNIQ_F485534DBA8D5FB31D775834 (id_program_eligibility, value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE credit_guaranty_program_choice_option ADD CONSTRAINT FK_10BA42694C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility ADD CONSTRAINT FK_E17147BB4C70DEF4 FOREIGN KEY (id_program) REFERENCES credit_guaranty_program (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility ADD CONSTRAINT FK_E17147BBF79B4C9A FOREIGN KEY (id_eligibility_criteria) REFERENCES credit_guaranty_eligibility_criteria (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration ADD CONSTRAINT FK_F485534DBA8D5FB3 FOREIGN KEY (id_program_eligibility) REFERENCES credit_guaranty_program_eligibility (id)');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration ADD CONSTRAINT FK_F485534DCB0F0BCB FOREIGN KEY (id_program_choice_option) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FB6A0C29E7927C744C70DEF4 ON credit_guaranty_program_contact (email, id_program)');
        $criteria = <<<INSERT_CRITERIA
INSERT INTO credit_guaranty_eligibility_criteria (public_id, field_alias, category, type, target_property_access_path, comparable, unit) VALUES
('77d246f8-8181-4d45-9a2a-268dd6795e70', 'juridical_person', 'general', 'bool', '', 0, NULL),
('3e2201f1-493f-475d-b84f-ee44e9065ea2', 'on_going_creation', 'general', 'bool', '', 0, NULL),
('a5ebc5fa-ebd6-450e-9c44-1aab84e65bbb', 'receiving_grant', 'general', 'bool', '', 0, NULL),
('0393c13d-1511-4d60-975e-ead448ed5d13', 'subsidiary', 'general', 'bool', '', 0, NULL),
('46c2d1b3-61fa-4d2f-a3f3-0336feecd2e2', 'borrower_type', 'profile', 'list', 'Unilend\\\CreditGuaranty\\\Entity\\\Borrower::type', 0, NULL),
('56d4b239-8b5a-41f0-9e65-4ced292b0c0c', 'company_name', 'profile', 'other', '', 0, NULL),
('bc84acbb-e1fe-4878-9e7b-7999c7a38282', 'company_address', 'profile', 'other', '', 0, NULL),
('4bd9fc81-aaaa-4753-913e-86c6b193fd85', 'borrower_identity', 'profile', 'other', '', 0, NULL),
('8f86ff38-1bfa-4608-a74d-7a40052d3f41', 'beneficiary_address', 'profile', 'other', '', 0, NULL),
('093a2142-ab5d-4b57-afb0-e8749131740b', 'tax_number', 'profile', 'other', '', 0, NULL)
INSERT_CRITERIA;
        $this->addSql($criteria);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility DROP FOREIGN KEY FK_E17147BBF79B4C9A');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration DROP FOREIGN KEY FK_F485534DCB0F0BCB');
        $this->addSql('ALTER TABLE credit_guaranty_program_eligibility_configuration DROP FOREIGN KEY FK_F485534DBA8D5FB3');
        $this->addSql('DROP TABLE credit_guaranty_eligibility_criteria');
        $this->addSql('DROP TABLE credit_guaranty_program_choice_option');
        $this->addSql('DROP TABLE credit_guaranty_program_eligibility');
        $this->addSql('DROP TABLE credit_guaranty_program_eligibility_configuration');
        $this->addSql('DROP INDEX UNIQ_FB6A0C29E7927C744C70DEF4 ON credit_guaranty_program_contact');
    }
}
