<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190730142422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE foncaris_request ADD comment LONGTEXT DEFAULT NULL');
        $this->addSql('INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES (\'fr_FR\', \'project-form\', \'foncaris-guarantee-comment-label\', \'Commentaire\', NOW())');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE foncaris_request DROP comment');
        $this->addSql('DELETE FROM translations WHERE section = \'project-form\' AND name = \'foncaris-guarantee-comment-label\'');
    }
}
