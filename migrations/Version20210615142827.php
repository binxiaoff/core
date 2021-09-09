<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210615142827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3882 CALS-3883 update borrower fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile' WHERE field_alias = 'creation_in_progress'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile' WHERE field_alias = 'company_naf_code'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile', target_property_access_path = 'borrower::subsidiary' WHERE field_alias = 'subsidiary'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile', target_property_access_path = 'borrower::siret' WHERE field_alias = 'siret'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile', target_property_access_path = 'borrower::address::country' WHERE field_alias = 'activity_country'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile', target_property_access_path = 'borrower::activityStartDate' WHERE field_alias = 'activity_start_date'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile', target_property_access_path = 'borrower::employeesNumber' WHERE field_alias = 'employees_number'");
        $this->addSql("UPDATE credit_guaranty_field SET category = 'profile', target_property_access_path = 'borrower::totalAssets::amount' WHERE field_alias = 'total_assets'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'project::fundingMoney::amount' WHERE field_alias = 'project_total_amount'");
        $this->addSql("UPDATE credit_guaranty_field SET target_property_access_path = 'financingObjects::loanMoney::amount' WHERE field_alias = 'loan_amount'");

        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'juridical_person'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'receiving_grant'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'company_address'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'siren'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'last_year_turnover'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = '5_years_average_turnover'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'grant_amount'");
        $this->addSql("DELETE FROM credit_guaranty_field WHERE field_alias = 'low_density_medical_area_exercise'");

        $fields = <<<'INSERT_FIELDS'
            INSERT INTO credit_guaranty_field (public_id, category, type, field_alias, target_property_access_path, comparable, unit, predefined_items) VALUES
            ('df8c4d9b-6978-4656-899c-0f083c0f22f2', 'profile', 'bool', 'young_farmer', 'borrower::youngFarmer', 0, NULL, NULL),
            ('406628f8-26a4-44a9-9742-074f86b313e2', 'profile', 'list', 'exploitation_size', 'borrower::exploitationSize', 0, NULL, NULL),
            ('fd5af2b2-81e7-44f4-a349-51d00e8e104b', 'profile', 'other', 'turnover', 'borrower::turnover::amount', 1, 'money', NULL);
            INSERT_FIELDS;
        $this->addSql($fields);
    }
}
