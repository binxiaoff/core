<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210218130307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Correct year type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant_rule CHANGE year year INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant_rule CHANGE year year VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
