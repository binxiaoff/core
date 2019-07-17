<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190717100314 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-92 Foncaris translations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATIONS'
            INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES
                ('fr_FR', 'tranche-form', 'credit-agricole-green-id-label', 'ID Green', NOW()),
                ('fr_FR', 'tranche-form', 'foncaris-funding-type-label', 'Nature du financement', NOW()),
                ('fr_FR', 'tranche-form', 'foncaris-security-label', 'Sureté', NOW()),
                ('fr_FR', 'project', 'publish-confirmation-message', '<p>Le dossier vient d‘être publié. Il est à présent possible de formuler une offre de participation sur ce dossier.</p><p><a href="%projectLink%">Consulter le dossier</p>', NOW()),
                ('fr_FR', 'project', 'publish-confirmation-title', 'Confirmation', NOW())
TRANSLATIONS
);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = \'tranche-form\' AND name in (\'credit-agricole-green-id-label\', \'foncaris-funding-type-label\', \'foncaris-security-label\')');
        $this->addSql('DELETE FROM translations WHERE section = \'project\' AND name in (\'publish-confirmation-message\', \'publish-confirmation-title\')');
    }
}
