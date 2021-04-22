<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210422111654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3687 Add arrangement project field in agency projet to record import source';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project ADD id_arrangement_project INT DEFAULT NULL, DROP source_public_id');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF55D6FCE7 FOREIGN KEY (id_arrangement_project) REFERENCES syndication_project (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_59B349BF55D6FCE7 ON agency_project (id_arrangement_project)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BF55D6FCE7');
        $this->addSql('DROP INDEX IDX_59B349BF55D6FCE7 ON agency_project');
        $this->addSql('ALTER TABLE agency_project ADD source_public_id VARCHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP id_arrangement_project');
    }
}
