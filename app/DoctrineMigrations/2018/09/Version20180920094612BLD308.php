<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180920094612BLD308 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $newTranslations = <<<NEWTRANSLATIONS
INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES
  ('fr_FR', 'lender-profile', 'security-activity-section-title', 'Activité et appareils', NOW()),
  ('fr_FR', 'lender-profile', 'security-activity-section-see-activity-button', 'Voir l''activité', NOW()),
  ('fr_FR', 'lender-profile', 'security-activity-and-devices-message', 'C''est la liste des appareils depuis lesquels vous vous êtes connectés à votre compte.', NOW()),
  ('fr_FR', 'lender-profile', 'security-activity-and-devices-edit-header-message', 'Appareils récemment utilisés', NOW()),
  ('fr_FR', 'lender-profile', 'security-activity-section-close-button', 'Fermer', NOW()),
  ('fr_FR', 'lender-profile', 'security-activity-and-devices-login-time', '{0} Maintenant actif.|{1} Actif il y a 1 minute.|[2, 60[ Actif il y a %minutes% minutes.|[60, 120[ Il y a 1 heure.|[120, 1440[ Il y a %hours% heures.|[1440, 2880[Hier.', NOW())
  ('fr_FR', 'lender-profile', 'security-activity-section-no-login-device', 'Impossible de charger l''historique de connexion et des appareils utilisés.', NOW())
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
            \'security-activity-section-title\',
            \'security-activity-section-see-activity-button\',
            \'security-activity-and-devices-message\',
            \'security-activity-and-devices-edit-header-message\',
            \'security-activity-section-close-button\',
            \'security-activity-and-devices-login-time\',
            \'security-activity-section-no-login-device\'
        )');
    }
}
