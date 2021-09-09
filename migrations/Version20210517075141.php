<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210517075141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3083 Make Agent extend AbstractProjectPartaker';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent ADD matriculation_number VARCHAR(9) NOT NULL, DROP siren, CHANGE rcs rcs VARCHAR(40) DEFAULT NULL, CHANGE capital_amount capital_amount NUMERIC(15, 2) NOT NULL, CHANGE capital_currency capital_currency VARCHAR(3) NOT NULL');
        $this->addSql('DROP INDEX IDX_59B349BFC80EDDAD ON agency_project');
        $this->addSql('ALTER TABLE agency_project DROP id_agent, DROP agent_display_name, DROP agent_legal_form, DROP head_office, DROP bank_institution, DROP bic, DROP iban, DROP agency_contact_first_name, DROP agency_contact_last_name, DROP agency_contact_parent_unit, DROP agency_contact_occupation, DROP agency_contact_email, DROP agency_contact_phone');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent ADD siren VARCHAR(9) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP matriculation_number, CHANGE rcs rcs VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE capital_amount capital_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE capital_currency capital_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_project ADD id_agent INT NOT NULL, ADD agent_display_name VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agent_legal_form VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD head_office VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD bank_institution VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD bic VARCHAR(11) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD iban VARCHAR(34) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_first_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_last_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_parent_unit VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_occupation VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_phone VARCHAR(35) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE INDEX IDX_59B349BFC80EDDAD ON agency_project (id_agent)');
    }
}
