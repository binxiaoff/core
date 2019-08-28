<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190828092140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-269: Update translation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET name = "update-success-message"  WHERE section = "user-profile-form" AND name = "success"');
        $this->addSql('UPDATE translations SET translation = "Modifier les informations"  WHERE section = "user-profile-form" AND name = "form-submit-button"');
        $this->addSql('UPDATE translations SET translation = "Téléphone portable"  WHERE section = "user-profile-form" AND name = "mobile-phone-label"');
        $this->addSql('UPDATE translations SET translation = "Vos informations ont bien été modifiées."  WHERE section = "user-profile-form" AND name = "update-success-message"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET name = "success"  WHERE section = "user-profile-form" AND name = "update-success-message"');
        $this->addSql('UPDATE translations SET translation = "Modifier mes infos"  WHERE section = "user-profile-form" AND name = "form-submit-button"');
        $this->addSql('UPDATE translations SET translation = "Votre téléphone portable"  WHERE section = "user-profile-form" AND name = "mobile-phone-label"');
        $this->addSql('UPDATE translations SET translation = "Vos infos ont bien été modifiées"  WHERE section = "user-profile-form" AND name = "update-success-message"');
    }
}
