<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190515154415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-101 Add project operation type column';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project ADD operation_type SMALLINT DEFAULT NULL AFTER id_project_status_history');
        $this->addSql('UPDATE project set operation_type = 1');
        $this->addSql('ALTER TABLE project MODIFY operation_type SMALLINT NOT NULL');

        $this->addSql('
            INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES
            (\'fr_FR\', \'project-creation\', \'debt-arrangement\', \'Arrangement de dette<br><small>pour un client</small>\', NOW()),
            (\'fr_FR\', \'project-creation\', \'syndication\', \'Syndication<br><small>d‘un encours client</small>\', NOW())
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project DROP operation_type');
    }
}
