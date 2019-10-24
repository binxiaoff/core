<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191024083800 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    public function getDescription(): string
    {
        return 'CALS-476 Delete LBO market segment';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $newId      = $this->connection->fetchColumn("SELECT id FROM market_segment WHERE label = 'corporate'");
        $previousId = $this->connection->fetchColumn("SELECT id FROM market_segment WHERE label = 'lbo'");

        $this->addSql("UPDATE project SET id_market_segment = {$newId} WHERE id_market_segment = {$previousId}");
        $this->addSql("UPDATE staff_market_segment SET market_segment_id = {$newId} WHERE market_segment_id = {$previousId}");

        $this->addSql("DELETE from translations WHERE section = 'market-segment' AND name = 'lbo'");
        $this->addSql("DELETE from market_segment WHERE id = {$previousId}");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("INSERT INTO market_segment(label) VALUES ('lbo')");
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'market-segment', 'lbo', 'LBO', NOW())");
    }
}
