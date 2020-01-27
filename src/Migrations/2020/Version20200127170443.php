<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20200127170443 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-805';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO market_segment (label) VALUES ('pro')");
        $this->addSql("INSERT INTO translations(locale, section, name, translation, added, updated) VALUES ('fr_FR', 'market-segment', 'pro', 'Pro', NOW(), NULL)");

        $this->addSql("UPDATE market_segment SET label = 'ppp' where label = 'infrastructure'");
        $this->addSql("UPDATE translations SET updated = NOW(), name = 'ppp', translation = 'PPP' WHERE name = 'infrastructure' AND section = 'market-segment'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM market_segment WHERE label = 'Pro'");
        $this->addSql("DELETE FROM translations WHERE name = 'Pro' AND section = 'market-segment'");

        $this->addSql("UPDATE market_segment SET label = 'infrastructure' where label = 'ppp'");
        $this->addSql("UPDATE translations SET updated = NOW(), name = 'infrastructure', translation = 'Infrastructure' WHERE name = 'PPP' AND section = 'market-segment'");
    }
}
