<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190823114620 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-269: Add translations for profile page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES 
            ("fr_FR", "user-profile-form", "mobile-phone-label", "Téléphone portable", NOW()),
            ("fr_FR", "user-profile-form", "form-submit-button", "Modifier les informations", NOW()),
            ("fr_FR", "user-profile-form", "update-success-message", "Vos informations ont bien été modifiées.", NOW()),
            ("fr_FR", "common", "mobile-phone-label", "Téléphone portable", NOW()),
            ("fr_FR", "account-init", "first-name-label", "Prénom", NOW()),
            ("fr_FR", "account-init", "last-name-label", "Nom", NOW()),
            ("fr_FR", "account-init", "job-function-label", "Fonction professionnelle", NOW()),
            ("fr_FR", "account-init", "phone-label", "Téléphone fixe", NOW()),
            ("fr_FR", "account-init", "identity-section", "Identité", NOW()),
            ("fr_FR", "account-init", "security-section", "Sécurité", NOW()),
            ("fr_FR", "account-init", "form-submit-button", "Initialiser le compte", NOW()),
            ("fr_FR", "account-init", "invalid-link-error-message", "Lien de réinitialisation de vos informations invalide ou expiré.", NOW()),
            ("fr_FR", "account-init", "page-title", "Initialisation de votre compte", NOW()),
            ("fr_FR", "account-init", "mobile-phone-label", "Téléphone portable", NOW())
        ');

        $this->addSql('ALTER TABLE clients ADD job_function VARCHAR(255) DEFAULT NULL AFTER id_nationaliy, CHANGE phone phone VARCHAR(35) DEFAULT NULL COMMENT \'(DC2Type:phone_number)\'');

        $this->addSql('DELETE FROM translations WHERE section = "password-init"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES
            ("fr_FR", "password-init", "invalid-link-error-message", "Lien de réinitialisation de mot de passe invalide ou expiré.", NOW()),
            ("fr_FR", "password-init", "page-title", "Initialisation de mot de passe", NOW())
        ');

        $this->addSql('DELETE FROM translations WHERE section = "user-profile-form" AND 
            name = "mobile-phone-label" OR 
            name = "form-submit-button" OR 
            name = "update-success-message"
        ');
        $this->addSql('DELETE FROM translations WHERE section = "common" AND name = "mobile-phone-label"');
        $this->addSql('DELETE FROM translations WHERE section = "account-init"');

        $this->addSql('ALTER TABLE clients DROP job_function, CHANGE phone phone VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
