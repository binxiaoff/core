<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Unilend\Core\DataFixtures\AbstractSQLFixtures;

class FieldFixtures extends AbstractSQLFixtures
{
    /**
     * @todo when it will be requested
     * to create in Borrower :                  juridical_person (bool)
     * to create in BorrowerBusinessActivity :  siren (string/other), activity_start_date (date/other), low_density_medical_area_exercise (bool)
     * to create in FinancingObject :           loan_deferral (int/other)
     */
    protected static string $sql = <<<'INSERT_FIELDS'
        INSERT INTO credit_guaranty_field (public_id, field_alias, category, type, target_property_access_path, comparable, unit, predefined_items) VALUES
        ('77d246f8-8181-4d45-9a2a-268dd6795e70', 'juridical_person', 'general', 'bool', '', 0, NULL, NULL),
        ('3e2201f1-493f-475d-b84f-ee44e9065ea2', 'creation_in_progress', 'general', 'bool', 'borrower::creationInProgress', 0, NULL, NULL),
        ('a5ebc5fa-ebd6-450e-9c44-1aab84e65bbb', 'receiving_grant', 'general', 'bool', 'borrowerBusinessActivity::receivingGrant', 0, NULL, NULL),
        ('0393c13d-1511-4d60-975e-ead448ed5d13', 'subsidiary', 'general', 'bool', 'borrowerBusinessActivity::subsidiary', 0, NULL, NULL),
        ('46c2d1b3-61fa-4d2f-a3f3-0336feecd2e2', 'borrower_type', 'profile', 'list', 'borrower::borrowerType', 0, NULL, NULL),
        ('56d4b239-8b5a-41f0-9e65-4ced292b0c0c', 'company_name', 'profile', 'other', 'borrower::companyName', 0, NULL, NULL),
        ('bc84acbb-e1fe-4878-9e7b-7999c7a38282', 'company_address', 'profile', 'other', 'borrowerBusinessActivity::address', 0, NULL, NULL),
        ('4bd9fc81-aaaa-4753-913e-86c6b193fd85', 'beneficiary_name', 'profile', 'other', 'borrower::beneficiaryName', 0, NULL, NULL),
        ('8f86ff38-1bfa-4608-a74d-7a40052d3f41', 'beneficiary_address', 'profile', 'other', 'borrower::address', 0, NULL, NULL),
        ('093a2142-ab5d-4b57-afb0-e8749131740b', 'tax_number', 'profile', 'other', 'borrower::taxNumber', 0, NULL, NULL),
        ('eef6e5ac-8de6-4084-a06b-dd2974141d94', 'legal_form', 'profile', 'list', 'borrower::legalForm', 0, NULL, '["SARL","SAS","SASU","EURL","SA","SELAS"]'),
        ('a2bf0ba4-9e56-43f2-94a5-5da47c80cadd', 'siren', 'activity', 'other', '', 0, NULL, NULL),
        ('f6ea8c30-48d1-4852-9c4a-5e1298f7f902', 'siret', 'activity', 'other', 'borrowerBusinessActivity::siret', 0, NULL, NULL),
        ('932afe50-582a-462c-b5cc-16cdd3f09c07', 'activity_country', 'activity', 'list', 'borrowerBusinessActivity::address::country', 0, NULL, '["FR"]'),
        ('d61a4e71-4438-46f1-b1a5-376f98566c06', 'activity_start_date', 'activity', 'other', '', 0, NULL, NULL),
        ('6c067265-5ff5-49f4-84f0-e511a4a7d42e', 'employees_number', 'activity', 'other', 'borrowerBusinessActivity::employeesNumber', 1, 'person', NULL),
        ('9865fb18-ba90-42ce-9455-e7c508acddd9', 'last_year_turnover', 'activity', 'other', 'borrowerBusinessActivity::lastYearTurnover', 1, 'money', NULL),
        ('a2e2cbad-75d3-42a4-83d9-aef63da4360e', '5_years_average_turnover', 'activity', 'other', 'borrowerBusinessActivity::fiveYearsAverageTurnover', 1, 'money', NULL),
        ('7cccbd98-6b99-4425-8f29-83a04027740c', 'borrower_naf_code', 'activity', 'list', 'borrowerBusinessActivity::borrowerNafCode', 0, NULL, NULL),
        ('938c689e-bddb-42b9-b84a-a00b18523e4f', 'total_assets', 'activity', 'other', 'borrowerBusinessActivity::totalAssets', 1, 'money', NULL),
        ('5e6b31a1-1007-4e8d-a1ce-9a38e62280b9', 'grant_amount', 'activity', 'other', 'borrowerBusinessActivity::grant', 1, 'money', NULL),
        ('d5cbbd4c-0101-4d6b-b816-146a1943ee2e', 'low_density_medical_area_exercise', 'activity', 'other', '', 1, 'money', NULL),
        ('23892bef-00b0-4df5-981e-32913e708a2b', 'investment_thematic', 'project', 'list', 'project::investmentThematic', 0, NULL, NULL),
        ('3dbe7d2c-2b78-4f72-ab52-ca3703e39f5b', 'project_total_amount', 'project', 'other', 'project::fundingMoney', 1, 'money', NULL),
        ('a2f8bca2-0662-4569-a334-57bd00972dcc', 'project_naf_code', 'project', 'list', 'project::projectNafCode', 1, NULL, NULL),
        ('8cb4b512-fa4c-4638-9a28-11d16b450459', 'financing_object', 'project', 'list', 'financingObjects::financingObject', 0, NULL, NULL),
        ('675056b4-49bb-40a7-bafe-9bcc86ad7b99', 'loan_type', 'loan', 'list', 'financingObjects::loanType', 0, NULL, '["term_loan","short_term","revolving_credit","stand_by","signature_commitment"]'),
        ('e9861e5b-0513-4c7b-9799-2f5a7dc267a4', 'loan_duration', 'loan', 'other', 'financingObjects::loanDuration', 0, NULL, null),
        ('f6f933e3-8164-430d-a670-390d0fc48311', 'loan_deferral', 'loan', 'other', '', 1, 'month', NULL),
        ('604ac6ee-86e2-49ab-aac0-bab8b1d20b30', 'loan_released_on_invoice', 'loan', 'bool', 'financingObjects::releasedOnInvoice', 0, NULL, NULL),
        ('61d56903-32f7-4ea7-beb9-142122202120', 'loan_amount', 'loan', 'other', 'financingObjects::loanMoney', 1, 'money', NULL);
        INSERT_FIELDS;
}
