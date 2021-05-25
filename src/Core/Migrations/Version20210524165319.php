<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210524165319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3781 [CreditGuaranty] add loan prefix in credit_guaranty_financing_object duration and money fields + update credit_guaranty_field table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object CHANGE money_amount loan_money_amount NUMERIC(15, 2) NOT NULL, CHANGE money_currency loan_money_currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object CHANGE duration loan_duration SMALLINT NOT NULL');

        $this->addSql("INSERT INTO credit_guaranty_field (public_id, field_alias, category, type, target_property_access_path, comparable, unit, predefined_items) VALUES ('61d56903-32f7-4ea7-beb9-142122202120', 'loan_object_amount', 'loan', 'other', 'Unilend\\CreditGuaranty\\Entity\\FinancingObject::loanMoney', 1, 'money', NULL)");

        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'creation_in_progress' WHERE field_alias = 'on_going_creation'");
        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'financing_object' WHERE field_alias = 'funding_object'");
        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'financing_object_amount' WHERE field_alias = 'funding_object_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile' WHERE field_alias = 'legal_form'");

        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Borrower::creationInProgress' WHERE field_alias = 'creation_in_progress'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::grant' WHERE field_alias = 'receiving_grant'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::subsidiary' WHERE field_alias = 'subsidiary'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Borrower::companyName' WHERE field_alias = 'company_name'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::address' WHERE field_alias = 'company_address'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Borrower::publicId' WHERE field_alias = 'borrower_identity'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Borrower::address' WHERE field_alias = 'beneficiary_address'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Borrower::taxNumber' WHERE field_alias = 'tax_number'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Borrower::legalForm' WHERE field_alias = 'legal_form'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::siret' WHERE field_alias = 'siret'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::address::country' WHERE field_alias = 'activity_country'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::employeesNumber' WHERE field_alias = 'employees_number'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::lastYearTurnover' WHERE field_alias = 'last_year_turnover'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::fiveYearsAverageTurnover' WHERE field_alias = '5_years_average_turnover'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::totalAssets' WHERE field_alias = 'total_assets'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::grant' WHERE field_alias = 'grant_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Project::investmentThematic' WHERE field_alias = 'investment_thematic'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Project::fundingMoney' WHERE field_alias = 'project_total_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\Project::nafNace::nafCode' WHERE field_alias = 'naf_code_project'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\FinancingObject::financingObject' WHERE field_alias = 'financing_object'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\FinancingObject::loanMoney' WHERE field_alias = 'financing_object_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\FinancingObject::loanType' WHERE field_alias = 'loan_type'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\FinancingObject::loanDuration' WHERE field_alias = 'loan_duration'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'Unilend\\CreditGuaranty\\Entity\\FinancingObject::releasedOnInvoice' WHERE field_alias = 'loan_released_on_invoice'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_financing_object CHANGE loan_money_amount money_amount NUMERIC(15, 2) NOT NULL, CHANGE loan_money_currency money_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object CHANGE loan_duration duration SMALLINT NOT NULL');

        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'loan_object_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'on_going_creation' WHERE field_alias = 'creation_in_progress'");
        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'funding_object' WHERE field_alias = 'financing_object'");
        $this->addSql("UPDATE credit_guaranty_field SET field_alias = 'funding_object_amount' WHERE field_alias = 'financing_object_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'activity' WHERE field_alias = 'legal_form'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = '' WHERE field_alias IN ('on_going_creation', 'receiving_grant', 'subsidiary', 'company_name', 'company_address', 'borrower_identity', 'beneficiary_address', 'tax_number', 'legal_form', 'siret', 'activity_country', 'employees_number', 'last_year_turnover', '5_years_average_turnover', 'total_assets', 'grant_amount', 'investment_thematic', 'project_total_amount', 'naf_code_project', 'funding_object', 'funding_object_amount', 'loan_type', 'loan_duration', 'loan_released_on_invoice')");
    }
}
