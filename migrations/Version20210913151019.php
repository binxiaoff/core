<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210913151019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Core] CALS-4429 remove third_party_syndicate field from agency_tranche table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_tranche DROP third_party_syndicate');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_tranche ADD third_party_syndicate VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
