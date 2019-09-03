<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190903080713 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'Add translation for Foncaris comment missing error';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "project-form", "foncaris-guarantee-comment-required", "Merci de saisir un commentaire", NOW())');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = "project-form" AND name = "foncaris-guarantee-comment-required"');
    }
}
