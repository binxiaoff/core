<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210105111411 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2829 Add agent information to agency project';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_project ADD agent_display_name VARCHAR(300) DEFAULT NULL, ADD agent_siren VARCHAR(9) DEFAULT NULL, ADD agent_legal_form VARCHAR(255) DEFAULT NULL, ADD head_office VARCHAR(255) DEFAULT NULL, ADD agent_rcs VARCHAR(255) DEFAULT NULL, ADD agent_registration_city VARCHAR(255) DEFAULT NULL, ADD agent_capital_amount NUMERIC(15, 2) DEFAULT NULL, ADD agent_capital_currency VARCHAR(3) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_project DROP agent_display_name, DROP agent_siren, DROP agent_legal_form, DROP head_office, DROP agent_rcs, DROP agent_registration_city, DROP agent_capital_amount, DROP agent_capital_currency');
    }
}
