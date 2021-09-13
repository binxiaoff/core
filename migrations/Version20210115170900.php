<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210115170900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3142 [Agency] Add borrowers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_borrower (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, corporate_name VARCHAR(100) NOT NULL, headquarter_address VARCHAR(100) NOT NULL, matriculation_number VARCHAR(100) NOT NULL, matriculation_city VARCHAR(50) NOT NULL, siren VARCHAR(9) NOT NULL, signatory_first_name VARCHAR(50) NOT NULL, signatory_last_name VARCHAR(50) NOT NULL, signatory_email VARCHAR(50) NOT NULL, referent_first_name VARCHAR(50) NOT NULL, referent_last_name VARCHAR(50) NOT NULL, referent_email VARCHAR(50) NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_C78A2C4FB5B48B91 (public_id), INDEX IDX_C78A2C4F166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4F166D1F9C FOREIGN KEY (project_id) REFERENCES agency_project (id)');
        $this->addSql('ALTER TABLE agency_borrower ADD added_by INT NOT NULL');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4F699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('CREATE INDEX IDX_C78A2C4F699B6BAF ON agency_borrower (added_by)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agency_borrower');
    }
}
