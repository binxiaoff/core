<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190522094902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-159 Add translations for cookie consent';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT INTO translations (locale, section, name, translation, added) VALUES
            (\'fr_FR\', \'cookie-consent\', \'message\', \'En poursuivant votre navigation sur ce site, vous acceptez l’utilisation de cookies dans les conditions prévues par notre politique de confidentialité.\', NOW()),
            (\'fr_FR\', \'cookie-consent\', \'dismiss-button\', \'Accepter\', NOW()),
            (\'fr_FR\', \'cookie-consent\', \'link-text\', \'En savoir plus\', NOW())
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM translations WHERE section = "cookie-consent" AND name IN (\'message\', \'dismiss-button\', \'link-text\')');
    }
}
