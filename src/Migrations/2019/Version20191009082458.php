<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191009082458 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-403 (Project Fee Type)';
    }

    public function up(Schema $schema): void
    {
        $translations = [
            'participation'  => 'Participation',
            'administrative' => 'Frais de dossier',
        ];

        foreach ($translations as $key => $translation) {
            $key = 'project_fee_type_' . $key;
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
