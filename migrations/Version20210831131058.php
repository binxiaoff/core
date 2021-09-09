<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210831131058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4517 add property_type field in credit_guaranty_field table + hydrate';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_field ADD property_type VARCHAR(255) NOT NULL');
        // delete ::amount
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'turnover' WHERE property_path = 'turnover::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'totalAssets' WHERE property_path = 'totalAssets::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'fundingMoney' WHERE property_path = 'fundingMoney::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'contribution' WHERE property_path = 'contribution::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'eligibleFeiCredit' WHERE property_path = 'eligibleFeiCredit::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'totalFeiCredit' WHERE property_path = 'totalFeiCredit::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'tangibleFeiCredit' WHERE property_path = 'tangibleFeiCredit::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'intangibleFeiCredit' WHERE property_path = 'intangibleFeiCredit::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'creditExcludingFei' WHERE property_path = 'creditExcludingFei::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'grant' WHERE property_path = 'grant::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'landValue' WHERE property_path = 'landValue::amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_path = 'bfrValue' WHERE property_path = 'bfrValue::amount'");
        // hydrate property_type
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'beneficiary_name'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'borrower_type'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'bool' WHERE field_alias = 'young_farmer'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'bool' WHERE field_alias = 'creation_in_progress'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'bool' WHERE field_alias = 'subsidiary'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'company_name'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'activity_street'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'activity_post_code'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'activity_city'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'activity_department'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'activity_country'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'DateTimeImmutable' WHERE field_alias = 'activity_start_date'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'siret'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'tax_number'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'legal_form'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'company_naf_code'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'int' WHERE field_alias = 'employees_number'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'exploitation_size'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'turnover'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'total_assets'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'bool' WHERE field_alias = 'receiving_grant'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'investment_street'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'investment_post_code'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'investment_city'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'string' WHERE field_alias = 'investment_department'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'investment_country'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'investment_thematic'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'investment_type'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'aid_intensity'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'additional_guaranty'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'agricultural_branch'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'project_total_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'project_contribution'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'eligible_fei_credit'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'total_fei_credit'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'tangible_fei_credit'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'intangible_fei_credit'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'credit_excluding_fei'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'project_grant'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'land_value'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'bool' WHERE field_alias = 'supporting_generations_renewal'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'financing_object_type'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'loan_naf_code'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'MoneyInterface' WHERE field_alias = 'bfr_value'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'loan_type'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'int' WHERE field_alias = 'loan_duration'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'int' WHERE field_alias = 'loan_deferral'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'loan_periodicity'");
        $this->addSql("UPDATE credit_guaranty_field SET property_type = 'ProgramChoiceOption' WHERE field_alias = 'investment_location'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_field DROP property_type');
    }
}
