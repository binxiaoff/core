<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210716101107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Remove syndicated field for tranche';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_tranche DROP syndicated');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_tranche ADD syndicated TINYINT(1) NOT NULL');
    }
}
