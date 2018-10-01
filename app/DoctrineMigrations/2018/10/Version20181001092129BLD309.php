<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20181001092129BLD309 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $newTranslations = <<<NEWTRANSLATIONS
INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES
  ('fr_FR', 'lender-profile', 'security-2fa-section-title', 'Authentification à deux facteurs', NOW()),
  ('fr_FR', 'lender-profile', 'security-2fa-section-edit-button', 'Modifier', NOW()),
  ('fr_FR', 'lender-profile', 'security-2fa-explanation-message', 'Utiliser un code sur une application d''authentification en plus de votre mot de passe.', NOW())
NEWTRANSLATIONS;

        $this->addSql($newTranslations);

    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('
        DELETE FROM translations 
        WHERE section = \'lender-profile\' 
        AND name IN (
            \'security-2fa-section-title\',
            \'security-2fa-section-edit-button\',
            \'security-2fa-explanation-message\'
        )');
    }
}
