<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210628144345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-4026 Remove Participation::responsability property';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE agency_participation ap
            INNER JOIN agency_participation_pool app ON ap.id_participation_pool = app.id
            INNER JOIN agency_project a ON app.id_project = a.id
            INNER JOIN agency_agent aa ON a.id = aa.id_project
            SET agent_commission_amount = 0, agent_commission_currency = a.global_funding_money_currency
            WHERE aa.id_company = ap.id_participant AND agent_commission_amount IS NULL
            SQL
);

        $arranger = (1 << 1);
        $this->addSql(<<<SQL
            UPDATE agency_participation ap
            INNER JOIN agency_participation_pool app ON ap.id_participation_pool = app.id 
            INNER JOIN agency_project a ON app.id_project = a.id
            SET ap.arranger_commission_amount = 0, ap.arranger_commission_currency = a.global_funding_money_currency
            WHERE responsibilities & {$arranger} = {$arranger}
            SQL
);
        $deputyArranger = (1 << 2);
        $this->addSql(<<<SQL
            UPDATE agency_participation ap
            INNER JOIN agency_participation_pool app ON ap.id_participation_pool = app.id
            INNER JOIN agency_project a ON app.id_project = a.id
            SET ap.deputy_arranger_commission_amount = 0, ap.deputy_arranger_commission_currency = a.global_funding_money_currency
            WHERE responsibilities & {$deputyArranger} = {$deputyArranger}
            SQL
);

        $this->addSql('ALTER TABLE agency_participation DROP responsibilities');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation ADD responsibilities INT NOT NULL COMMENT \'(DC2Type:bitmask)\'');

        $this->addSql('UPDATE agency_participation SET responsibilities = 1 WHERE agent_commission_amount IS NOT NULL');
        $this->addSql('UPDATE agency_participation SET responsibilities = responsibilities + 2 WHERE arranger_commission_amount IS NOT NULL');
        $this->addSql('UPDATE agency_participation SET responsibilities = responsibilities + 4 WHERE deputy_arranger_commission_amount IS NOT NULL');
    }
}
