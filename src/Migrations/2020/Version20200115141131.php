<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20200115141131 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-643 Remove fees';
    }

    public function up(Schema $schema): void
    {
        $translations = [
            'non_utilisation' => 'Non utilisation',
            'utilisation'     => 'Utilisation',
        ];

        foreach ($translations as $key => $translation) {
            $this->addSql("DELETE FROM translations WHERE locale = 'fr_FR' AND section = 'fee-type' AND name = 'tranche_fee_type_{$key}'");
        }

        $this->addSql("DELETE FROM tranche_fee WHERE fee_type IN ('utilisation', 'first_drawdown')");
    }

    public function down(Schema $schema): void
    {
        $translations = [
            'non_utilisation' => 'Non utilisation',
            'utilisation'     => 'Utilisation',
        ];

        foreach ($translations as $key => $translation) {
            $key = 'tranche_fee_type_' . $key;
            $this->addSql("INSERT INTO translations (locale, section, name, translation, added, updated) VALUES ('fr_FR', 'fee-type', '{$key}', '{$translation}', NOW(), NULL) ON DUPLICATE KEY UPDATE translation = VALUES(translation)");
        }
    }
}
