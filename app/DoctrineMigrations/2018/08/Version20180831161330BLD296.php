<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180831161330BLD296 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $updateTranslations = <<<UPDATETRANSLATIONS
UPDATE translations SET name = 'contact-section-submit-button' WHERE section = 'lender-profile' AND name = 'phone-section-submit-button';
UPDATE translations SET name = 'contact-section-cancel-button' WHERE section = 'lender-profile' AND name = 'phone-section-cancel-button';
UPDATE translations SET name = 'information-tab-contact-section-title', translation = 'Vos coordonnées' WHERE section = 'lender-profile' AND name = 'information-tab-phone-section-title';
UPDATE translations SET name = 'information-tab-contact-form-success-message', translation = 'Vos coordonnées ont été mis à jour' WHERE section = 'lender-profile' AND name = 'information-tab-phone-form-success-message';
UPDATE translations SET name = 'identification-error-existing-email' WHERE section = 'lender-profile' AND name = 'security-identification-error-existing-email'
UPDATETRANSLATIONS;

        $this->addSql($updateTranslations);

        $this->addSql('DELETE FROM translations WHERE section = \'lender-profile\' AND name = \'security-identification-form-success-message\'');

    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $updateTranslations = <<<UPDATETRANSLATIONS
UPDATE translations SET name = 'phone-section-submit-button' WHERE section = 'lender-profile' AND name = 'contact-section-submit-button';
UPDATE translations SET name = 'phone-section-cancel-button' WHERE section = 'lender-profile' AND name = 'contact-section-cancel-button';
UPDATE translations SET name = 'information-tab-phone-section-title', translation = 'Vos coordonnées téléphoniques' WHERE section = 'lender-profile' AND name = 'information-tab-contact-section-title';
UPDATE translations SET name = 'information-tab-phone-form-success-message', translation = 'Vos coordonnées téléphoniques ont été mis à jour' WHERE section = 'lender-profile' AND name ='information-tab-contact-form-success-message';
UPDATE translations SET name = 'security-identification-error-existing-email' WHERE section = 'lender-profile' AND name = 'identification-error-existing-email';
UPDATETRANSLATIONS;

        $this->addSql($updateTranslations);

        $this->addSql('
        INSERT IGNORE INTO translations (locale, section, name, translation, added)
        VALUES (\'fr_FR\', \'lender-profile\', \'security-identification-form-success-message\', \'Les données concernant vos identifiants ont bien été mis à jour\', NOW())'
        );
    }
}
