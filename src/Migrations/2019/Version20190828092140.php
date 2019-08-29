<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190828092140 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-286 add translations and job attribute for Clients';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET name = "update-success-message"  WHERE section = "user-profile-form" AND name = "success"');
        $this->addSql('UPDATE translations SET section = "account-init"  WHERE section = "password-init"');
        $this->addSql('UPDATE translations SET translation = "Modifier les informations"  WHERE section = "user-profile-form" AND name = "form-submit-button"');
        $this->addSql('UPDATE translations SET translation = "Téléphone portable"  WHERE section = "user-profile-form" AND name = "mobile-phone-label"');
        $this->addSql('UPDATE translations SET translation = "Vos informations ont bien été modifiées."  WHERE section = "user-profile-form" AND name = "update-success-message"');
        $this->addSql('UPDATE translations SET translation = "Initialiser le compte"  WHERE section = "account-init" AND name = "form-submit-button"');
        $this->addSql('UPDATE translations SET translation = "Initialisation de votre compte"  WHERE section = "account-init" AND name = "page-title"');
        $this->addSql('UPDATE translations SET translation = "Lien de réinitialisation de vos informations invalide ou expiré."  WHERE section = "account-init" AND name = "invalid-link-error-message"');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "account-init", "first-name-label", "Prénom", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "account-init", "last-name-label", "Nom", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "account-init", "job-function-label", "Fonction professionnelle", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "account-init", "phone-label", "Téléphone fixe", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "account-init", "identity-section", "Identité", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "account-init", "security-section", "Sécurité", NOW())');

        $this->addSql('ALTER TABLE clients ADD job_function VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(35) DEFAULT NULL COMMENT \'(DC2Type:phone_number)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET name = "success"  WHERE section = "user-profile-form" AND name = "update-success-message"');
        $this->addSql('UPDATE translations SET section = "password-init"  WHERE section = "account-init"');
        $this->addSql('UPDATE translations SET translation = "Modifier mes infos"  WHERE section = "user-profile-form" AND name = "form-submit-button"');
        $this->addSql('UPDATE translations SET translation = "Votre téléphone portable"  WHERE section = "user-profile-form" AND name = "mobile-phone-label"');
        $this->addSql('UPDATE translations SET translation = "Vos infos ont bien été modifiées"  WHERE section = "user-profile-form" AND name = "update-success-message"');
        $this->addSql('UPDATE translations SET translation = "Initialiser le mot de passe"  WHERE section = "password-init" AND name = "form-submit-button"');
        $this->addSql('UPDATE translations SET translation = "Initialisation de votre mot de passe"  WHERE section = "password-init" AND name = "page-title"');
        $this->addSql('UPDATE translations SET translation = "Lien de réinitialisation de mot de passe invalide ou expiré."  WHERE section = "password-init" AND name = "invalid-link-error-message"');

        $this->addSql('DELETE FROM translations WHERE section = "password-init" AND name = "first-name-label"');
        $this->addSql('DELETE FROM translations WHERE section = "password-init" AND name = "last-name-label"');
        $this->addSql('DELETE FROM translations WHERE section = "password-init" AND name = "job-function-label"');
        $this->addSql('DELETE FROM translations WHERE section = "password-init" AND name = "phone-label"');
        $this->addSql('DELETE FROM translations WHERE section = "password-init" AND name = "identity-section"');
        $this->addSql('DELETE FROM translations WHERE section = "password-init" AND name = "security-section"');

        $this->addSql('ALTER TABLE clients DROP job_function, CHANGE phone phone VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
