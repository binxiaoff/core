<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190906072638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-335';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "account-profile", "cgu-download", "Télécharger les dernières CGU signées", NOW())');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "account-profile" AND name = "cgu-download"');
    }
}
