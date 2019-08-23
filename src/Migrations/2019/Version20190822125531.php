<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190822125531 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-269-add-number-phone: Add mobile phone translation for password-init context, and change type of mobile from string to phone_number';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "password-init", "mobile-phone-label", "Téléphone portable", NOW())');
        $this->addSql('ALTER TABLE clients CHANGE mobile mobile VARCHAR(35) DEFAULT NULL COMMENT \'(DC2Type:phone_number)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "password-init" AND name = "mobile-phone-label"');
        $this->addSql('ALTER TABLE clients CHANGE mobile mobile VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
