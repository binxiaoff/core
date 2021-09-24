<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210525115540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adjust agency model in order to match actual project data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent CHANGE capital_amount capital_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE capital_currency capital_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower CHANGE capital_amount capital_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE capital_currency capital_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_covenant CHANGE name name VARCHAR(150) NOT NULL, CHANGE contract_extract contract_extract VARCHAR(5000) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation ADD agent_commission_amount NUMERIC(15, 2) DEFAULT NULL, ADD agent_commission_currency VARCHAR(3) DEFAULT NULL, ADD arranger_commission_amount NUMERIC(15, 2) DEFAULT NULL, ADD arranger_commission_currency VARCHAR(3) DEFAULT NULL, ADD deputy_arranger_commission_amount NUMERIC(15, 2) DEFAULT NULL, ADD deputy_arranger_commission_currency VARCHAR(3) DEFAULT NULL, DROP agent_commission, DROP arranger_commission, DROP deputy_arranger_commission, CHANGE capital_amount capital_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE capital_currency capital_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE core_company CHANGE siren siren VARCHAR(9) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent CHANGE capital_amount capital_amount NUMERIC(15, 2) NOT NULL, CHANGE capital_currency capital_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_borrower CHANGE capital_amount capital_amount NUMERIC(15, 2) NOT NULL, CHANGE capital_currency capital_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_covenant CHANGE name name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE contract_extract contract_extract VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_participation ADD agent_commission NUMERIC(5, 4) DEFAULT NULL, ADD arranger_commission NUMERIC(5, 4) DEFAULT NULL, ADD deputy_arranger_commission NUMERIC(5, 4) DEFAULT NULL, DROP agent_commission_amount, DROP agent_commission_currency, DROP arranger_commission_amount, DROP arranger_commission_currency, DROP deputy_arranger_commission_amount, DROP deputy_arranger_commission_currency, CHANGE capital_amount capital_amount NUMERIC(15, 2) NOT NULL, CHANGE capital_currency capital_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE core_company CHANGE siren siren VARCHAR(9) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
