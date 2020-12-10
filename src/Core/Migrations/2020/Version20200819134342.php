<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200819134342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2155 Fix market segment ids';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0;');
        $this->addSql('UPDATE project SET id_market_segment = id_market_segment + 100;');
        $this->addSql('UPDATE staff_market_segment SET market_segment_id = market_segment_id + 100;');
        $this->addSql("UPDATE market_segment SET id = id + 100 WHERE 1;");

        $newMarketSegments = [
            "public_collectivity" => 1,
              "energy" => 2,
              "corporate" => 3,
              "real_estate_development" => 4,
              "ppp" => 5,
              "agriculture" => 6,
              "patrimonial" => 7,
              "pro" => 8,
        ];

        foreach ($newMarketSegments as $marketSegment => $id) {
            $this->addSql("UPDATE project SET id_market_segment = $id WHERE id_market_segment = (SELECT id FROM market_segment WHERE label = '$marketSegment');");
            $this->addSql("UPDATE staff_market_segment SET market_segment_id = $id WHERE market_segment_id = (SELECT id FROM market_segment WHERE label = '$marketSegment');");
            $this->addSql("UPDATE market_segment SET id = $id WHERE label = '$marketSegment';");
        }

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(Schema $schema): void
    {
        // can't guess previous market segment ids !
    }
}
