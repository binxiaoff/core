<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210419182642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3687 Add field to to record if arrangement project has been imported in Agency';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE syndication_project ADD agency_imported TINYINT(1) NOT NULL');

        $this->addSql('ALTER TABLE agency_project ADD source_public_id VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE syndication_project DROP agency_imported');

        $this->addSql('ALTER TABLE agency_project DROP source_public_id');
    }
}
