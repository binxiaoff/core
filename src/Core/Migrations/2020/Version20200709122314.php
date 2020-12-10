<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200709122314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1921 remove committee_status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation DROP committee_status');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_participation ADD committee_status VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
