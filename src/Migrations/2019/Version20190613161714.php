<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20190613161714 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-216 Update lending rate translations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET translation = \'Index de référence\' WHERE section = \'lending-form\' AND name = \'index-type\'');
        $this->addSql('UPDATE translations SET translation = \'Taux\' WHERE section = \'lending-form\' AND name = \'margin\'');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES (\'fr_FR\', \'lending-rate-form\', \'index-type-required\', \'Merci de sélectionner un index de référence\', NOW())');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE translations SET translation = \'Taux de référence\' WHERE section = \'lending-form\' AND name = \'index-type\'');
        $this->addSql('UPDATE translations SET translation = \'Taux d’intérêt\' WHERE section = \'lending-form\' AND name = \'margin\'');
        $this->addSql('DELETE FROM translations WHERE section = \'lending-rate-form\' AND name = \'index-type-required\'');
    }
}
