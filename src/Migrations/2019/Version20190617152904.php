<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190617152904 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-213 Add confirmation translation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES (\'fr_FR\', \'utility\', \'delete-confirmation\', \'Voulez-vous vraiment supprimer cet élément ?\', NOW())');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section = \'utility\' AND name = \'delete-confirmation\'');
    }
}
