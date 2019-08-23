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
        return 'CALS-269-add-number-phone: Add translations for profile page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "user-profile-form", "mobile-phone-label", "Votre téléphone portable", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "user-profile-form", "form-submit-button", "Modifier mes infos", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "user-profile-form", "success", "Vos infos ont bien été modifiées", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "common", "mobile-phone-label", "Téléphone portable", NOW())');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "user-profile-form" AND name = "mobile-phone-label"');
        $this->addSql('DELETE FROM translations WHERE section = "user-profile-form" AND name = "form-submit-button"');
        $this->addSql('DELETE FROM translations WHERE section = "user-profile-form" AND name = "success"');
        $this->addSql('DELETE FROM translations WHERE section = "common" AND name = "mobile-phone-label"');
    }
}
