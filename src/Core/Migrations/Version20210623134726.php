<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210623134726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3886 CALS-3887 update credit_guaranty_financing_object table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP FOREIGN KEY FK_6AECF0F562547109');
        $this->addSql('DROP INDEX IDX_6AECF0F562547109 ON credit_guaranty_financing_object');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object
            ADD supporting_generations_renewal TINYINT(1) DEFAULT NULL,
            ADD name VARCHAR(255) DEFAULT NULL,
            ADD id_financing_object_type INT DEFAULT NULL,
            ADD id_loan_naf_code INT DEFAULT NULL,
            ADD bfr_value_amount NUMERIC(15, 2) DEFAULT NULL,
            ADD bfr_value_currency VARCHAR(3) DEFAULT NULL,
            ADD loan_deferral SMALLINT DEFAULT NULL,
            ADD id_loan_periodicity INT DEFAULT NULL,
            ADD id_investment_location INT DEFAULT NULL,
            DROP id_financing_object,
            CHANGE id_loan_type id_loan_type INT DEFAULT NULL,
            CHANGE loan_duration loan_duration SMALLINT DEFAULT NULL,
            CHANGE released_on_invoice main_loan TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F533160DF3 FOREIGN KEY (id_financing_object_type) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F5EFE69DFD FOREIGN KEY (id_loan_naf_code) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F58277CF29 FOREIGN KEY (id_loan_periodicity) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F521C8CC1C FOREIGN KEY (id_investment_location) REFERENCES credit_guaranty_program_choice_option (id)');
        $this->addSql('CREATE INDEX IDX_6AECF0F533160DF3 ON credit_guaranty_financing_object (id_financing_object_type)');
        $this->addSql('CREATE INDEX IDX_6AECF0F5EFE69DFD ON credit_guaranty_financing_object (id_loan_naf_code)');
        $this->addSql('CREATE INDEX IDX_6AECF0F58277CF29 ON credit_guaranty_financing_object (id_loan_periodicity)');
        $this->addSql('CREATE INDEX IDX_6AECF0F521C8CC1C ON credit_guaranty_financing_object (id_investment_location)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP FOREIGN KEY FK_6AECF0F533160DF3');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP FOREIGN KEY FK_6AECF0F5EFE69DFD');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP FOREIGN KEY FK_6AECF0F58277CF29');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP FOREIGN KEY FK_6AECF0F521C8CC1C');
        $this->addSql('DROP INDEX IDX_6AECF0F533160DF3 ON credit_guaranty_financing_object');
        $this->addSql('DROP INDEX IDX_6AECF0F5EFE69DFD ON credit_guaranty_financing_object');
        $this->addSql('DROP INDEX IDX_6AECF0F58277CF29 ON credit_guaranty_financing_object');
        $this->addSql('DROP INDEX IDX_6AECF0F521C8CC1C ON credit_guaranty_financing_object');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD id_financing_object INT NOT NULL, DROP id_financing_object_type, DROP id_loan_naf_code, DROP id_loan_periodicity, DROP id_investment_location, DROP supporting_generations_renewal, DROP name, DROP loan_deferral, DROP bfr_value_amount, DROP bfr_value_currency, CHANGE id_loan_type id_loan_type INT NOT NULL, CHANGE loan_duration loan_duration SMALLINT NOT NULL, CHANGE main_loan released_on_invoice TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD CONSTRAINT FK_6AECF0F562547109 FOREIGN KEY (id_financing_object) REFERENCES credit_guaranty_program_choice_option (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_6AECF0F562547109 ON credit_guaranty_financing_object (id_financing_object)');
    }
}
