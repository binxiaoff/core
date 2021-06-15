<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Unilend\Core\DataFixtures\AbstractSQLFixtures;

class FieldFixtures extends AbstractSQLFixtures
{
    /**
     * @todo when it will be requested
     * to create in FinancingObject : loan_deferral (int/other)
     */
    protected static string $sql = <<<'INSERT_FIELDS'
        INSERT INTO credit_guaranty_field (public_id, category, type, field_alias, target_property_access_path, comparable, unit, predefined_items) VALUES
        ('4bd9fc81-aaaa-4753-913e-86c6b193fd85', 'profile', 'other', 'beneficiary_name', 'borrower::beneficiaryName', 0, NULL, NULL),
        ('46c2d1b3-61fa-4d2f-a3f3-0336feecd2e2', 'profile', 'list', 'borrower_type', 'borrower::borrowerType', 0, NULL, NULL),
        ('df8c4d9b-6978-4656-899c-0f083c0f22f2', 'profile', 'bool', 'young_farmer', 'borrower::youngFarmer', 0, NULL, NULL),
        ('3e2201f1-493f-475d-b84f-ee44e9065ea2', 'profile', 'bool', 'creation_in_progress', 'borrower::creationInProgress', 0, NULL, NULL),
        ('0393c13d-1511-4d60-975e-ead448ed5d13', 'profile', 'bool', 'subsidiary', 'borrower::subsidiary', 0, NULL, NULL),
        ('56d4b239-8b5a-41f0-9e65-4ced292b0c0c', 'profile', 'other', 'company_name', 'borrower::companyName', 0, NULL, NULL),
        ('8f86ff38-1bfa-4608-a74d-7a40052d3f41', 'profile', 'other', 'beneficiary_address', 'borrower::address', 0, NULL, NULL),
        ('932afe50-582a-462c-b5cc-16cdd3f09c07', 'profile', 'list', 'activity_country', 'borrower::address::country', 0, NULL, '["FR"]'),
        ('d61a4e71-4438-46f1-b1a5-376f98566c06', 'profile', 'other', 'activity_start_date', 'borrower::activityStartDate', 0, NULL, NULL),
        ('f6ea8c30-48d1-4852-9c4a-5e1298f7f902', 'profile', 'other', 'siret', 'borrower::siret', 0, NULL, NULL),
        ('093a2142-ab5d-4b57-afb0-e8749131740b', 'profile', 'other', 'tax_number', 'borrower::taxNumber', 0, NULL, NULL),
        ('eef6e5ac-8de6-4084-a06b-dd2974141d94', 'profile', 'list', 'legal_form', 'borrower::legalForm', 0, NULL, '["SARL","SAS","SASU","EURL","SA","SELAS"]'),
        ('7cccbd98-6b99-4425-8f29-83a04027740c', 'profile', 'list', 'company_naf_code', 'borrower::companyNafCode', 0, NULL, NULL),
        ('6c067265-5ff5-49f4-84f0-e511a4a7d42e', 'profile', 'other', 'employees_number', 'borrower::employeesNumber', 1, 'person', NULL),
        ('406628f8-26a4-44a9-9742-074f86b313e2', 'profile', 'list', 'exploitation_size', 'borrower::exploitationSize', 0, NULL, NULL),
        ('fd5af2b2-81e7-44f4-a349-51d00e8e104b', 'profile', 'other', 'turnover', 'borrower::turnover::amount', 1, 'money', NULL),
        ('938c689e-bddb-42b9-b84a-a00b18523e4f', 'profile', 'other', 'total_assets', 'borrower::totalAssets::amount', 1, 'money', NULL),
        ('23892bef-00b0-4df5-981e-32913e708a2b', 'project', 'list', 'investment_thematic', 'project::investmentThematic', 0, NULL, NULL),
        ('3dbe7d2c-2b78-4f72-ab52-ca3703e39f5b', 'project', 'other', 'project_total_amount', 'project::fundingMoney::amount', 1, 'money', NULL),
        ('a2f8bca2-0662-4569-a334-57bd00972dcc', 'project', 'list', 'project_naf_code', 'project::projectNafCode', 0, NULL, NULL),
        ('8cb4b512-fa4c-4638-9a28-11d16b450459', 'project', 'list', 'financing_object', 'financingObjects::financingObject', 0, NULL, NULL),
        ('604ac6ee-86e2-49ab-aac0-bab8b1d20b30', 'loan', 'bool', 'loan_released_on_invoice', 'financingObjects::releasedOnInvoice', 0, NULL, NULL),
        ('675056b4-49bb-40a7-bafe-9bcc86ad7b99', 'loan', 'list', 'loan_type', 'financingObjects::loanType', 0, NULL, '["term_loan","short_term","revolving_credit","stand_by","signature_commitment"]'),
        ('61d56903-32f7-4ea7-beb9-142122202120', 'loan', 'other', 'loan_amount', 'financingObjects::loanMoney::amount', 1, 'money', NULL),
        ('e9861e5b-0513-4c7b-9799-2f5a7dc267a4', 'loan', 'other', 'loan_duration', 'financingObjects::loanDuration', 0, NULL, null),
        ('f6f933e3-8164-430d-a670-390d0fc48311', 'loan', 'other', 'loan_deferral', '', 1, 'month', NULL);
        INSERT_FIELDS;
}
