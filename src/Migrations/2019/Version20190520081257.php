<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190520081257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-111 Update project form translations';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "project-form", "lead-manager-label", "Chef de file", NOW())');
        $this->addSql('UPDATE translations set translation = "RUN emprunteur" WHERE section = "project-form" AND name = "run-label"');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');
        $this->addSql('DELETE FROM translations WHERE section = "project-form" AND name = "lead-manager-label"');
        $this->addSql('UPDATE translations set translation = "RUN" WHERE section = "project-form" AND name = "run-label"');
    }
}
