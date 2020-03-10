<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200310125342 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-1209';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE zz_versioned_project_participation (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX IDX_85175A86A78D87A7 (logged_at), INDEX IDX_85175A86F85E0677 (username), INDEX IDX_85175A86232D562B69684D7DBF1CD3C3 (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_participation ADD confidentiality_disclaimer_document_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC475495C6D5FD2 FOREIGN KEY (confidentiality_disclaimer_document_id) REFERENCES attachment (id)');
        $this->addSql('CREATE INDEX IDX_7FC475495C6D5FD2 ON project_participation (confidentiality_disclaimer_document_id)');
        $this->addSql('ALTER TABLE project_participation_contact ADD confidentiality_disclaimer_document_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation_contact ADD CONSTRAINT FK_41530AB35C6D5FD2 FOREIGN KEY (confidentiality_disclaimer_document_id) REFERENCES attachment (id)');
        $this->addSql('CREATE INDEX IDX_41530AB35C6D5FD2 ON project_participation_contact (confidentiality_disclaimer_document_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE zz_versioned_project_participation');
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC475495C6D5FD2');
        $this->addSql('DROP INDEX IDX_7FC475495C6D5FD2 ON project_participation');
        $this->addSql('ALTER TABLE project_participation DROP confidentiality_disclaimer_document_id');
        $this->addSql('ALTER TABLE project_participation_contact DROP FOREIGN KEY FK_41530AB35C6D5FD2');
        $this->addSql('DROP INDEX IDX_41530AB35C6D5FD2 ON project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_contact DROP confidentiality_disclaimer_document_id');
    }
}
