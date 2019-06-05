<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190514151240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids ADD rate_floor NUMERIC(4, 2) DEFAULT NULL after rate_margin');
        $this->addSql('ALTER TABLE tranche ADD rate_floor NUMERIC(4, 2) DEFAULT NULL after rate_margin');
        $this->addSql('ALTER TABLE loans ADD rate_floor NUMERIC(4, 2) DEFAULT NULL after rate_margin');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "lending-form", "floor", "Floorés à en %", NOW())');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids DROP rate_floor');
        $this->addSql('ALTER TABLE loans DROP rate_floor');
        $this->addSql('ALTER TABLE tranche DROP rate_floor');
        $this->addSql('DELETE FROM translations WHERE section = "lending-form" AND name = "floor"');
    }
}
