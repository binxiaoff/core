<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210309081644 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'Update term model';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE agency_term_history (id INT AUTO_INCREMENT NOT NULL, id_term INT DEFAULT NULL, id_document INT DEFAULT NULL, borrower_comment LONGTEXT DEFAULT NULL, agent_comment LONGTEXT DEFAULT NULL, borrower_input VARCHAR(255) DEFAULT NULL, validation TINYINT(1) DEFAULT NULL, validation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', breach TINYINT(1) NOT NULL, breach_comment LONGTEXT DEFAULT NULL, waiver TINYINT(1) DEFAULT NULL, waiver_comment LONGTEXT DEFAULT NULL, granted_delay INT DEFAULT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A804ADB22E2FFB8F (id_term), INDEX IDX_A804ADB288B266E3 (id_document), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_term_history ADD CONSTRAINT FK_A804ADB22E2FFB8F FOREIGN KEY (id_term) REFERENCES agency_term (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_term_history ADD CONSTRAINT FK_A804ADB288B266E3 FOREIGN KEY (id_document) REFERENCES core_file (id)');
        $this->addSql('DROP TABLE agency_term_answer');
        $this->addSql('ALTER TABLE agency_term ADD id_document INT DEFAULT NULL, ADD borrower_comment LONGTEXT DEFAULT NULL, ADD borrower_input VARCHAR(255) DEFAULT NULL, ADD borrower_input_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD validation TINYINT(1) DEFAULT NULL, ADD validation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD agent_comment LONGTEXT DEFAULT NULL, ADD breach TINYINT(1) NOT NULL, ADD breach_comment LONGTEXT DEFAULT NULL, ADD waiver TINYINT(1) DEFAULT NULL, ADD waiver_comment LONGTEXT DEFAULT NULL, ADD granted_delay INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_term ADD CONSTRAINT FK_B208FB8588B266E3 FOREIGN KEY (id_document) REFERENCES core_file (id)');
        $this->addSql('CREATE INDEX IDX_B208FB8588B266E3 ON agency_term (id_document)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE agency_term_answer (id INT AUTO_INCREMENT NOT NULL, id_term INT DEFAULT NULL, id_document INT DEFAULT NULL, validation TINYINT(1) DEFAULT NULL, validation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', borrower_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, agent_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, public_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', borrower_input VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, waiver TINYINT(1) DEFAULT NULL, granted_delay INT DEFAULT NULL, breach TINYINT(1) NOT NULL, breach_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, waiver_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_19E4CF92E2FFB8F (id_term), INDEX IDX_19E4CF988B266E3 (id_document), UNIQUE INDEX UNIQ_19E4CF9B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE agency_term_answer ADD CONSTRAINT FK_19E4CF92E2FFB8F FOREIGN KEY (id_term) REFERENCES agency_term (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_term_answer ADD CONSTRAINT FK_19E4CF988B266E3 FOREIGN KEY (id_document) REFERENCES core_file (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE agency_term_history');
        $this->addSql('ALTER TABLE agency_term DROP FOREIGN KEY FK_B208FB8588B266E3');
        $this->addSql('DROP INDEX IDX_B208FB8588B266E3 ON agency_term');
        $this->addSql('ALTER TABLE agency_term DROP id_document, DROP borrower_comment, DROP borrower_input, DROP borrower_input_date, DROP validation, DROP validation_date, DROP agent_comment, DROP breach, DROP breach_comment, DROP waiver, DROP waiver_comment, DROP granted_delay');
    }
}
