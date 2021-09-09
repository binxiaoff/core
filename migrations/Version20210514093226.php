<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210514093226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Remove silent_syndication field (dynamicly computed)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency_project DROP silent_syndication');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency_project ADD silent_syndication TINYINT(1) NOT NULL');
    }
}
