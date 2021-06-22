<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210622072950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3884 CALS-3885 update credit_guaranty_project table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D025EFE69DFD');
        $this->addSql('DROP INDEX IDX_A452D025EFE69DFD ON credit_guaranty_project');

        $this->addSql('ALTER TABLE credit_guaranty_project
            ADD detail VARCHAR(1200) DEFAULT NULL,
            ADD address_street VARCHAR(100) DEFAULT NULL,
            ADD address_post_code VARCHAR(10) DEFAULT NULL,
            ADD address_city VARCHAR(30) DEFAULT NULL,
            ADD address_department VARCHAR(30) DEFAULT NULL,
            ADD id_address_country INT DEFAULT NULL,
            ADD id_aid_intensity INT NOT NULL,
            ADD id_additional_guaranty INT NOT NULL,
            ADD id_agricultural_branch INT NOT NULL,
            ADD contribution_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD contribution_currency VARCHAR(3) DEFAULT NULL,
            ADD eligible_fei_credit_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD eligible_fei_credit_currency VARCHAR(3) DEFAULT NULL,
            ADD total_fei_credit_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD total_fei_credit_currency VARCHAR(3) DEFAULT NULL,
            ADD physical_fei_credit_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD physical_fei_credit_currency VARCHAR(3) DEFAULT NULL,
            ADD intangible_fei_credit_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD intangible_fei_credit_currency VARCHAR(3) DEFAULT NULL,
            ADD credit_excluding_fei_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD credit_excluding_fei_currency VARCHAR(3) DEFAULT NULL,
            ADD grant_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD grant_currency VARCHAR(3) DEFAULT NULL,
            ADD land_value_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD land_value_currency VARCHAR(3) DEFAULT NULL,
            CHANGE id_naf_code id_investment_type INT NOT NULL');

        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D02516C18177 FOREIGN KEY (id_investment_type) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D025706B56E6 FOREIGN KEY (id_aid_intensity) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D0251E84C52C FOREIGN KEY (id_additional_guaranty) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D025921DD360 FOREIGN KEY (id_agricultural_branch) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D025E1C8FD76 FOREIGN KEY (id_address_country) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_A452D02516C18177 ON credit_guaranty_project (id_investment_type)');
        $this->addSql('CREATE INDEX IDX_A452D025706B56E6 ON credit_guaranty_project (id_aid_intensity)');
        $this->addSql('CREATE INDEX IDX_A452D0251E84C52C ON credit_guaranty_project (id_additional_guaranty)');
        $this->addSql('CREATE INDEX IDX_A452D025921DD360 ON credit_guaranty_project (id_agricultural_branch)');
        $this->addSql('CREATE INDEX IDX_A452D025E1C8FD76 ON credit_guaranty_project (id_address_country)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D02516C18177');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D025706B56E6');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D0251E84C52C');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D025921DD360');
        $this->addSql('ALTER TABLE credit_guaranty_project DROP FOREIGN KEY FK_A452D025E1C8FD76');
        $this->addSql('DROP INDEX IDX_A452D02516C18177 ON credit_guaranty_project');
        $this->addSql('DROP INDEX IDX_A452D025706B56E6 ON credit_guaranty_project');
        $this->addSql('DROP INDEX IDX_A452D0251E84C52C ON credit_guaranty_project');
        $this->addSql('DROP INDEX IDX_A452D025921DD360 ON credit_guaranty_project');
        $this->addSql('DROP INDEX IDX_A452D025E1C8FD76 ON credit_guaranty_project');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD id_naf_code INT NOT NULL, DROP id_investment_type, DROP id_aid_intensity, DROP id_additional_guaranty, DROP id_agricultural_branch, DROP id_address_country, DROP detail, DROP address_street, DROP address_post_code, DROP address_city, DROP address_department, DROP contribution_amount, DROP contribution_currency, DROP eligible_fei_credit_amount, DROP eligible_fei_credit_currency, DROP total_fei_credit_amount, DROP total_fei_credit_currency, DROP physical_fei_credit_amount, DROP physical_fei_credit_currency, DROP intangible_fei_credit_amount, DROP intangible_fei_credit_currency, DROP credit_excluding_fei_amount, DROP credit_excluding_fei_currency, DROP grant_amount, DROP grant_currency, DROP land_value_amount, DROP land_value_currency');
        $this->addSql('ALTER TABLE credit_guaranty_project ADD CONSTRAINT FK_A452D025EFE69DFD FOREIGN KEY (id_naf_code) REFERENCES credit_guaranty_program_choice_option (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A452D025EFE69DFD ON credit_guaranty_project (id_naf_code)');
    }
}
