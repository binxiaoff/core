<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191003162259 extends AbstractMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-374 Create translation for password reset errors.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'forgotten-password', 'incorrect-password', 'Le mot de passe doit contenir au moins 6 caractères et doit contenir au moins une minuscule et une majuscule.', NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "forgotten-password" and name = "incorrect-password"');
    }
}
