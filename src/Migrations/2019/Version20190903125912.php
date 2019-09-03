<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190903125912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-249 Add project image';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EEC53D045F ON project (image)');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "project-request", "image-section-title", "Image", NOW())');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project DROP image');
        $this->addSql('DELETE FROM translations WHERE section = "project-request" AND name = "image-section-title"');
    }
}
