<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190725100029 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-273 Add translations for Foncaris attribut form error messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATIONS'
            INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES
                ('fr_FR', 'tranche-form', 'foncaris-funding-type-required', 'Merci de saisir une nature du financement', NOW()),
                ('fr_FR', 'tranche-form', 'foncaris-security-required', 'Merci de sélectionner une ou plusieurs sureté', NOW())
TRANSLATIONS
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = \'tranche-form\' AND name in (\'foncaris-funding-type-required\', \'foncaris-security-required\')');
    }
}
