<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210511220357 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agency_agent (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_agent INT NOT NULL, display_name VARCHAR(300) DEFAULT NULL, siren VARCHAR(9) DEFAULT NULL, legal_form VARCHAR(255) DEFAULT NULL, head_office VARCHAR(255) DEFAULT NULL, rcs VARCHAR(255) DEFAULT NULL, bank_institution VARCHAR(255) DEFAULT NULL, bic VARCHAR(11) DEFAULT NULL, iban VARCHAR(34) DEFAULT NULL, public_id VARCHAR(36) NOT NULL, capital_amount NUMERIC(15, 2) DEFAULT NULL, capital_currency VARCHAR(3) DEFAULT NULL, agency_contact_first_name VARCHAR(255) DEFAULT NULL, agency_contact_last_name VARCHAR(255) DEFAULT NULL, agency_contact_parent_unit VARCHAR(255) DEFAULT NULL, agency_contact_occupation VARCHAR(255) DEFAULT NULL, agency_contact_email VARCHAR(255) DEFAULT NULL, agency_contact_phone VARCHAR(35) DEFAULT NULL, UNIQUE INDEX UNIQ_284713B3B5B48B91 (public_id), UNIQUE INDEX UNIQ_284713B3F12E799E (id_project), INDEX IDX_284713B3C80EDDAD (id_agent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE agency_agent_member (id INT AUTO_INCREMENT NOT NULL, id_agent INT NOT NULL, id_user INT NOT NULL, added_by INT NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_925D3B7BB5B48B91 (public_id), INDEX IDX_925D3B7BC80EDDAD (id_agent), INDEX IDX_925D3B7B6B3CA4B (id_user), INDEX IDX_925D3B7B699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_agent ADD CONSTRAINT FK_284713B3F12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_agent ADD CONSTRAINT FK_284713B3C80EDDAD FOREIGN KEY (id_agent) REFERENCES core_company (id)');
        $this->addSql('ALTER TABLE agency_agent_member ADD CONSTRAINT FK_925D3B7BC80EDDAD FOREIGN KEY (id_agent) REFERENCES agency_agent (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_agent_member ADD CONSTRAINT FK_925D3B7B6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_agent_member ADD CONSTRAINT FK_925D3B7B699B6BAF FOREIGN KEY (added_by) REFERENCES core_user (id)');
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFC80EDDAD');
        $this->addSql('DROP INDEX IDX_59B349BFC80EDDAD ON agency_project');
        $this->addSql('ALTER TABLE agency_project DROP id_agent, DROP agent_display_name, DROP agent_siren, DROP agent_legal_form, DROP head_office, DROP agent_rcs, DROP agent_capital_amount, DROP agent_capital_currency, DROP bank_institution, DROP bic, DROP iban, DROP agency_contact_first_name, DROP agency_contact_last_name, DROP agency_contact_parent_unit, DROP agency_contact_occupation, DROP agency_contact_email, DROP agency_contact_phone');
        $this->addSql('DROP INDEX UNIQ_B74A64DBBF3D7168609C91B2 ON core_naf_nace');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B74A64DBBF3D7168 ON core_naf_nace (naf_code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B74A64DB609C91B2 ON core_naf_nace (nace_code)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency_agent_member DROP FOREIGN KEY FK_925D3B7BC80EDDAD');
        $this->addSql('DROP TABLE agency_agent');
        $this->addSql('DROP TABLE agency_agent_member');
        $this->addSql('ALTER TABLE agency_project ADD id_agent INT NOT NULL, ADD agent_display_name VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agent_siren VARCHAR(9) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agent_legal_form VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD head_office VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agent_rcs VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agent_capital_amount NUMERIC(15, 2) DEFAULT NULL, ADD agent_capital_currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD bank_institution VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD bic VARCHAR(11) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD iban VARCHAR(34) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_first_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_last_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_parent_unit VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_occupation VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD agency_contact_phone VARCHAR(35) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFC80EDDAD FOREIGN KEY (id_agent) REFERENCES core_company (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_59B349BFC80EDDAD ON agency_project (id_agent)');
        $this->addSql('DROP INDEX UNIQ_B74A64DBBF3D7168 ON core_naf_nace');
        $this->addSql('DROP INDEX UNIQ_B74A64DB609C91B2 ON core_naf_nace');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B74A64DBBF3D7168609C91B2 ON core_naf_nace (naf_code, nace_code)');
    }
}
