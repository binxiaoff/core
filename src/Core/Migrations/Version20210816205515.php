<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210816205515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Syndication] CALS-3912 Augment length of fundingSpecificity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project CHANGE funding_specificity funding_specificity VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_project CHANGE funding_specificity funding_specificity VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project CHANGE funding_specificity funding_specificity VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_project CHANGE funding_specificity funding_specificity VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
