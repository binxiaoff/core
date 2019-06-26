<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190625083822 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CLAS-156 attachment translations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATIONS'
            INSERT INTO translations (locale, section, name, translation, added) VALUES
                ('fr_FR', 'attachment', 'type-column-label', 'Type', NOW()),
                ('fr_FR', 'attachment', 'description-column-label', 'Description', NOW()),
                ('fr_FR', 'attachment', 'original-file-name-column-label', 'Nom du fichier', NOW()),
                ('fr_FR', 'attachment', 'download-button-tooltip', 'Télécharger', NOW()),
                ('fr_FR', 'attachment', 'detach-button-tooltip', 'Archiver', NOW()),
                ('fr_FR', 'attachment', 'no-attachment-info-message', 'Vous n’avez aucun document associé à votre dossier.', NOW()),
                ('fr_FR', 'attachment', 'detach-popup-confirmation-message', 'Voulez-vous vraiment archiver ce document ?', NOW())
TRANSLATIONS
        );
        $this->addSql('UPDATE translations SET translation = \'Description\' WHERE section = \'attachment-form\' AND name = \'description-placeholder\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = \'attachment\' AND name in (
        \'no-attachment-info-message\', \'description-column-label\', \'original-file-name-column-label\', \'download-button-tooltip\', \'detach-button-tooltip\', \'detach-popup-confirmation-message\')');
    }
}
