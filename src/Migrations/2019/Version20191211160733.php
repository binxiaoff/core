<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191211160733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE tranche SET rate_floor_type = "none" WHERE rate_floor_type is NULL');
        $this->addSql('UPDATE tranche_offer SET rate_floor_type = "none" WHERE rate_floor_type is NULL');
        $this->addSql('ALTER TABLE tranche_offer CHANGE rate_floor_type rate_floor_type VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tranche_offer CHANGE rate_floor_type rate_floor_type VARCHAR(20) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('UPDATE tranche_offer SET rate_floor_type = NULL WHERE rate_floor_type = "none"');
        $this->addSql('UPDATE tranche SET rate_floor_type = NULL WHERE rate_floor_type = "none"');
    }
}
