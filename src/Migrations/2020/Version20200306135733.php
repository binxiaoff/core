<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200306135733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1209';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation ADD confidentiality_disclaimer_document_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC475495C6D5FD2 FOREIGN KEY (confidentiality_disclaimer_document_id) REFERENCES attachment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FC475495C6D5FD2 ON project_participation (confidentiality_disclaimer_document_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC475495C6D5FD2');
        $this->addSql('DROP INDEX UNIQ_7FC475495C6D5FD2 ON project_participation');
        $this->addSql('ALTER TABLE project_participation DROP confidentiality_disclaimer_document_id');
    }
}
