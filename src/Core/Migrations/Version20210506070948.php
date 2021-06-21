<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210506070948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3744 [Agency] Add new fields for siren';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE agency_borrower SET matriculation_number = REGEXP_SUBSTR(matriculation_number, '[0-9]{9}')");
        $this->addSql('ALTER TABLE agency_borrower ADD rcs VARCHAR(40) DEFAULT NULL, CHANGE matriculation_number matriculation_number VARCHAR(9) NOT NULL');
        $this->addSql('ALTER TABLE agency_participation ADD matriculation_number VARCHAR(9) NOT NULL, ADD rcs VARCHAR(40) DEFAULT NULL, ADD capital_amount NUMERIC(15, 2) NOT NULL, ADD capital_currency VARCHAR(3) NOT NULL');
        $this->addSql('UPDATE agency_participation pa INNER JOIN agency_project pr ON pr.id_agent = pa.id_participant SET pa.matriculation_number = pr.agent_siren WHERE pr.agent_siren IS NOT NULL');
        $this->addSql(<<<'SQL'
            UPDATE agency_participation pa
            INNER JOIN agency_project pr ON pr.id_agent = pa.id_participant SET pa.capital_amount = pr.agent_capital_amount, pa.capital_currency = pr.agent_capital_currency
            WHERE pr.agent_capital_amount IS NOT NULL AND pr.agent_capital_currency IS NOT NULL
            SQL
);
        $this->addSql('ALTER TABLE agency_project DROP agent_siren, DROP agent_rcs, DROP agent_capital_amount, DROP agent_capital_currency');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP rcs, CHANGE matriculation_number matriculation_number VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_project ADD agent_siren VARCHAR(9) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agent_rcs VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agent_capital_amount NUMERIC(15, 2) DEFAULT NULL, ADD agent_capital_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_participation DROP matriculation_number, DROP rcs, DROP capital_amount, DROP capital_currency');
    }
}
