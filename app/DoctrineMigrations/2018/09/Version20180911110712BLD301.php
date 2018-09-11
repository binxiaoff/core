<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180911110712BLD301 extends AbstractMigration
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only executed safely on "mysql".');

        $insertTranslations = <<<INSERTTRANSLATIONS
INSERT INTO translations (locale, section, name, translation, added) VALUES
  ('fr_FR', 'lender-profile', 'security-password-section-requirements', 'Votre mot de passe doit contenir :', NOW()),
  ('fr_FR', 'lender-profile', 'security-password-section-has-lower-upper-char-check', 'Des lettres majuscules et minuscules', NOW()),
  ('fr_FR', 'lender-profile', 'security-password-section-has-digit-check', 'Au moins un chiffre', NOW()),
  ('fr_FR', 'lender-profile', 'security-password-section-has-min-length-check', 'Au moins 8 caractères', NOW())
INSERTTRANSLATIONS;

        $this->addSql($insertTranslations);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only executed safely on "mysql".');

        $deleteTranslations = <<<DELETETRANSLATIONS
DELETE FROM translations
 WHERE section = 'lender-profile'
  AND name IN ('security-password-section-requirements',
   'security-password-section-has-lower-upper-char-check',
   'security-password-section-has-digit-check',
   'security-password-section-has-min-length-check')
DELETETRANSLATIONS;

        $this->addSql($deleteTranslations);
    }
}
