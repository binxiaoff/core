<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200221163541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1194 Delete embeddable Permission';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_organizer DROP permission_permission');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_organizer ADD permission_permission SMALLINT DEFAULT 1 NOT NULL');
    }
}
