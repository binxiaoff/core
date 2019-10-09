<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191009080931 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-404 (Tranche Fee Type)';
    }

    public function up(Schema $schema): void
    {
        $translations = [
            'non_utilisation' => 'Non utilisation',
            'commitment'      => 'Engagement',
            'utilisation'     => 'Utilisation',
            'first_drawdown'  => 'Premier tirage',
        ];

        foreach ($translations as $key => $translation) {
            $key = 'tranche_fee_type_' . $key;
            $this->addSql(
                <<<SQL
INSERT INTO translations (locale, section, name, translation, added, updated) VALUES ('fr_FR', 'fee-type', '{$key}', '{$translation}', NOW(), NULL) ON DUPLICATE KEY UPDATE translation = VALUES(translation)
SQL
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(true, 'There is no need for a down for this migration');
    }
}
