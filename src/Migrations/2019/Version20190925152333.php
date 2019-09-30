<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190925152333 extends AbstractMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-351 Add new agriculture market';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO market_segment(label) VALUES("agriculture")');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "market-segment", "agriculture", "Agriculture", NOW())');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM market_segment WHERE label = "agriculture"');
        $this->addSql('DELETE FROM translations WHERE section = "market-segment" AND name = "agriculture"');
    }
}
