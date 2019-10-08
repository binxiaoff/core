<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191008145629 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-402 (Update market segment list)';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $marketSegments = [
            'corporate'               => 'Entreprises',
            'lbo'                     => 'LBO',
            'public_collectivity'     => 'Collectivités publiques',
            'agriculture'             => 'Agriculture',
            'real_estate_development' => 'Promotion immobilière',
            'infrastructure'          => 'Infrastructure',
            'energy'                  => 'Unifergie',
            'patrimonial'             => 'Patrimonial',
        ];

        $this->addSql("DELETE FROM translations WHERE section = 'market-segment'");
        foreach ($marketSegments as $label => $translation) {
            $this->addSql(
                <<<SQL
INSERT INTO translations(locale, section, name, translation, added, updated) 
VALUES ('fr_FR', 'market-segment', '{$label}', '{$translation}', NOW(), NULL)
SQL
            );
        }

        $existing = $this->connection->fetchAll('SELECT label FROM market_segment');
        $existing = array_column($existing, 'label');

        $toInsert = array_diff_key($marketSegments, array_flip($existing));
        foreach ($toInsert as $label => $translation) {
            $this->addSql("INSERT INTO market_segment(label) VALUES ('{$label}')");
        }

        $finalList = array_map([$this->connection, 'quote'], array_keys($marketSegments));
        $finalList = implode(',', $finalList);
        $this->addSql("DELETE FROM market_segment WHERE label NOT IN ({$finalList})");
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(true, 'This migration only concern data. There is no need for a down');
    }
}
