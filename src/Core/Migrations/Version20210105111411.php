<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210105111411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2829 Add agent information to agency project';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project ADD id_agent INT NOT NULL, ADD added_by INT NOT NULL, ADD agent_display_name VARCHAR(300) DEFAULT NULL, ADD agent_siren VARCHAR(9) DEFAULT NULL, ADD agent_legal_form VARCHAR(255) DEFAULT NULL, ADD head_office VARCHAR(255) DEFAULT NULL, ADD agent_rcs VARCHAR(255) DEFAULT NULL, ADD agent_registration_city VARCHAR(255) DEFAULT NULL, ADD agent_capital_amount NUMERIC(15, 2) DEFAULT NULL, ADD agent_capital_currency VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFC80EDDAD FOREIGN KEY (id_agent) REFERENCES core_company (id)');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id)');
        $this->addSql('CREATE INDEX IDX_59B349BFC80EDDAD ON agency_project (id_agent)');
        $this->addSql('CREATE INDEX IDX_59B349BF699B6BAF ON agency_project (added_by)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFC80EDDAD');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BF699B6BAF');
        $this->addSql('DROP INDEX IDX_59B349BFC80EDDAD ON agency_project');
        $this->addSql('DROP INDEX IDX_59B349BF699B6BAF ON agency_project');
        $this->addSql('ALTER TABLE agency_project DROP id_agent, DROP added_by, DROP agent_display_name, DROP agent_siren, DROP agent_legal_form, DROP head_office, DROP agent_rcs, DROP agent_registration_city, DROP agent_capital_amount, DROP agent_capital_currency');
    }
}
