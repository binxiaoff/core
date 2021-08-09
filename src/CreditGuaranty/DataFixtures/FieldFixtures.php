<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\DataFixtures;

use KLS\Core\DataFixtures\AbstractSQLFixtures;

class FieldFixtures extends AbstractSQLFixtures
{
    protected static string $sql = <<<'INSERT_FIELDS'
        INSERT INTO credit_guaranty_field (public_id, category, type, field_alias, reservation_property_name, property_path, object_class, comparable, unit, predefined_items) VALUES
        ('4bd9fc81-aaaa-4753-913e-86c6b193fd85', 'profile', 'other', 'beneficiary_name', 'borrower', 'beneficiaryName', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('46c2d1b3-61fa-4d2f-a3f3-0336feecd2e2', 'profile', 'list', 'borrower_type', 'borrower', 'borrowerType', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('df8c4d9b-6978-4656-899c-0f083c0f22f2', 'profile', 'bool', 'young_farmer', 'borrower', 'youngFarmer', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('3e2201f1-493f-475d-b84f-ee44e9065ea2', 'profile', 'bool', 'creation_in_progress', 'borrower', 'creationInProgress', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('0393c13d-1511-4d60-975e-ead448ed5d13', 'profile', 'bool', 'subsidiary', 'borrower', 'subsidiary', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('56d4b239-8b5a-41f0-9e65-4ced292b0c0c', 'profile', 'other', 'company_name', 'borrower', 'companyName', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('7518eade-0825-4464-8b07-c372fd69300c', 'profile', 'other', 'activity_street', 'borrower', 'addressStreet', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('ffa27a62-e831-4d9f-bdda-d7dbdd5ab57f', 'profile', 'other', 'activity_post_code', 'borrower', 'addressPostCode', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('e5c18fa8-adb2-4123-ad49-d7c6b95eae70', 'profile', 'other', 'activity_city', 'borrower', 'addressCity', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('c4876798-0ed5-4808-9ef9-c1810b158c4f', 'profile', 'other', 'activity_department', 'borrower', 'addressDepartment', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('932afe50-582a-462c-b5cc-16cdd3f09c07', 'profile', 'list', 'activity_country', 'borrower', 'addressCountry', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('d61a4e71-4438-46f1-b1a5-376f98566c06', 'profile', 'other', 'activity_start_date', 'borrower', 'activityStartDate', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('f6ea8c30-48d1-4852-9c4a-5e1298f7f902', 'profile', 'other', 'siret', 'borrower', 'siret', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('093a2142-ab5d-4b57-afb0-e8749131740b', 'profile', 'other', 'tax_number', 'borrower', 'taxNumber', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('eef6e5ac-8de6-4084-a06b-dd2974141d94', 'profile', 'list', 'legal_form', 'borrower', 'legalForm', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, '["SARL","SAS","SASU","EURL","SA","SELAS"]'),
        ('7cccbd98-6b99-4425-8f29-83a04027740c', 'profile', 'list', 'company_naf_code', 'borrower', 'companyNafCode', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('6c067265-5ff5-49f4-84f0-e511a4a7d42e', 'profile', 'other', 'employees_number', 'borrower', 'employeesNumber', 'KLS\\CreditGuaranty\\Entity\\Borrower', 1, 'person', NULL),
        ('406628f8-26a4-44a9-9742-074f86b313e2', 'profile', 'list', 'exploitation_size', 'borrower', 'exploitationSize', 'KLS\\CreditGuaranty\\Entity\\Borrower', 0, NULL, NULL),
        ('fd5af2b2-81e7-44f4-a349-51d00e8e104b', 'profile', 'other', 'turnover', 'borrower', 'turnover::amount', 'KLS\\CreditGuaranty\\Entity\\Borrower', 1, 'money', NULL),
        ('938c689e-bddb-42b9-b84a-a00b18523e4f', 'profile', 'other', 'total_assets', 'borrower', 'totalAssets::amount', 'KLS\\CreditGuaranty\\Entity\\Borrower', 1, 'money', NULL),

        ('386f841e-3771-4a35-a54a-e4169fd80d63', 'project', 'bool', 'receiving_grant', 'project', 'receivingGrant', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('be7be094-9b78-4d97-adaa-d08d1edaec67', 'project', 'other', 'investment_street', 'project', 'addressStreet', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('833b9e3a-c958-4792-adc1-41b05332f965', 'project', 'other', 'investment_post_code', 'project', 'addressPostCode', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('91fc4bb3-0b1d-4d0d-ba46-54564c30a775', 'project', 'other', 'investment_city', 'project', 'addressCity', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('c904c2fb-6940-49ef-b9c3-9961c38ef70e', 'project', 'other', 'investment_department', 'project', 'addressDepartment', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('674d1e2d-cf35-4c05-9ee6-69a5bbe698d6', 'project', 'list', 'investment_country', 'project', 'addressCountry', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('23892bef-00b0-4df5-981e-32913e708a2b', 'project', 'list', 'investment_thematic', 'project', 'investmentThematic', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('ee365095-5e4e-4c02-bb45-b71506cbc42b', 'project', 'list', 'investment_type', 'project', 'investmentType', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('5b621a54-17a8-4226-8251-ef8bc35c0aae', 'project', 'list', 'aid_intensity', 'project', 'aidIntensity', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('c7b28186-59e6-4032-8abc-144f8c89e6db', 'project', 'list', 'additional_guaranty', 'project', 'additionalGuaranty', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('d2b8441d-c2ef-4b11-b01e-ed145232995b', 'project', 'list', 'agricultural_branch', 'project', 'agriculturalBranch', 'KLS\\CreditGuaranty\\Entity\\Project', 0, NULL, NULL),
        ('3dbe7d2c-2b78-4f72-ab52-ca3703e39f5b', 'project', 'other', 'project_total_amount', 'project', 'fundingMoney::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
        ('58fd1213-d881-494e-abbc-3dfddb672370', 'project', 'other', 'project_contribution', 'project', 'contribution::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
        ('3a14f624-43c5-4b44-9cec-6128298df493', 'project', 'other', 'eligible_fei_credit', 'project', 'eligibleFeiCredit::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
        ('a7d8d27a-67d4-4add-90c9-76a787451ca2', 'project', 'other', 'total_fei_credit', 'project', 'totalFeiCredit::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
        ('29a1d576-44f7-4bf7-8a20-4c354a4206ab', 'project', 'other', 'tangible_fei_credit', 'project', 'tangibleFeiCredit::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
        ('838516c5-82a2-40b7-b8f8-d9fc7890f923', 'project', 'other', 'intangible_fei_credit', 'project', 'intangibleFeiCredit::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
        ('d00e3272-d334-45d2-a93a-1078e6356537', 'project', 'other', 'credit_excluding_fei', 'project', 'creditExcludingFei::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
        ('ff0d7855-bdfb-448f-b1b5-ef32e4b87c60', 'project', 'other', 'project_grant', 'project', 'grant::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),
        ('fa5dbf8e-ad12-46f4-b2dd-bb12cfbb77ae', 'project', 'other', 'land_value', 'project', 'landValue::amount', 'KLS\\CreditGuaranty\\Entity\\Project', 1, 'money', NULL),

        ('b7da24f1-4d1c-426e-9e25-ef2773113d2a', 'loan', 'bool', 'supporting_generations_renewal', 'financingObjects', 'supportingGenerationsRenewal', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, NULL),
        ('8cb4b512-fa4c-4638-9a28-11d16b450459', 'loan', 'list', 'financing_object_type', 'financingObjects', 'financingObjectType', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, NULL),
        ('58acfc84-3d39-4a9d-98bd-acd884a0e74b', 'loan', 'list', 'loan_naf_code', 'financingObjects', 'loanNafCode', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, NULL),
        ('55a1b77f-1b2d-40a8-8f77-a4fa2ae5f292', 'loan', 'other', 'bfr_value', 'financingObjects', 'bfrValue::amount', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 1, 'money', NULL),
        ('675056b4-49bb-40a7-bafe-9bcc86ad7b99', 'loan', 'list', 'loan_type', 'financingObjects', 'loanType', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, '["term_loan","short_term","revolving_credit","stand_by","signature_commitment"]'),
        ('e9861e5b-0513-4c7b-9799-2f5a7dc267a4', 'loan', 'other', 'loan_duration', 'financingObjects', 'loanDuration', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 1, 'month', null),
        ('f6f933e3-8164-430d-a670-390d0fc48311', 'loan', 'other', 'loan_deferral', 'financingObjects', 'loanDeferral', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 1, 'month', NULL),
        ('93319782-8cfd-474f-bfe8-ab5aae88456b', 'loan', 'list', 'loan_periodicity', 'financingObjects', 'loanPeriodicity', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, '["monthly","quarterly","semi_annually","annually"]'),
        ('61ad5da2-1ae1-4c0b-b3bd-ce42fc0bea3b', 'loan', 'list', 'investment_location', 'financingObjects', 'investmentLocation', 'KLS\\CreditGuaranty\\Entity\\FinancingObject', 0, NULL, NULL);
        INSERT_FIELDS;
}
