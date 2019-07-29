<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190724134932 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET section = "project-publish", name = "confirmation-title"     WHERE section = "project" AND name = "publish-confirmation-title"');
        $this->addSql('UPDATE translations SET section = "project-publish", name = "confirmation-message"   WHERE section = "project" AND name = "publish-confirmation-message"');
        $this->addSql('UPDATE translations SET section = "project-publish", name = "submit-button-label"    WHERE section = "project" AND name = "publish-confirmation-button-label"');
        $this->addSql('UPDATE translations SET section = "project-publish", name = "foncaris-section-title" WHERE section = "project-request" AND name = "foncaris-section-title"');

        $this->addSql(
            <<<'INSERTTRANS'
INSERT INTO translations (locale, section, name, translation, added) VALUES
  ('fr_FR', 'project-publish', 'page-title', 'Publication de votre dossier', NOW()),
  ('fr_FR', 'project-publish', 'process-explanation', 'Vous êtes sur le point de publier votre dossier. Suite à sa publication, le dossier sera visible sur la place marché pour les prêteurs que vous avez sélectionnés. Ces derniers recevront également un email de notification.', NOW())
INSERTTRANS
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET section = "project", name = "publish-confirmation-title"        WHERE section = "project-publish" AND name = "confirmation-title"');
        $this->addSql('UPDATE translations SET section = "project", name = "publish-confirmation-message"      WHERE section = "project-publish" AND name = "confirmation-message"');
        $this->addSql('UPDATE translations SET section = "project", name = "publish-confirmation-button-label" WHERE section = "project-publish" AND name = "submit-button-label"');
        $this->addSql('UPDATE translations SET section = "project-request", name = "foncaris-section-title"    WHERE section = "project-publish" AND name = "foncaris-section-title"');

        $this->addSql('DELETE FROM translations WHERE section = "project-publish" AND name IN ("page-title", "process-explanation")');
    }
}
