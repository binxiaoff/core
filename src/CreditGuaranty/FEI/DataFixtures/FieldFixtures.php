<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use KLS\Core\DataFixtures\AbstractSQLFixtures;

class FieldFixtures extends AbstractSQLFixtures
{
    protected static string $sql = <<<'INSERT_FIELDS'
        INSERT INTO credit_guaranty_field (
            public_id, tag, category, type,
            field_alias, reservation_property_name, property_path, property_type,
            object_class, comparable, unit, predefined_items
        ) VALUES
        (
            '4bd9fc81-aaaa-4753-913e-86c6b193fd85', 'eligibility', 'profile', 'other',
            'beneficiary_name', 'borrower', 'beneficiaryName', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            '46c2d1b3-61fa-4d2f-a3f3-0336feecd2e2', 'eligibility', 'profile', 'list',
            'borrower_type', 'borrower', 'borrowerType', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'df8c4d9b-6978-4656-899c-0f083c0f22f2', 'eligibility', 'profile', 'bool',
            'young_farmer', 'borrower', 'youngFarmer', 'bool',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            '3e2201f1-493f-475d-b84f-ee44e9065ea2', 'eligibility', 'profile', 'bool',
            'creation_in_progress', 'borrower', 'creationInProgress', 'bool',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            '0393c13d-1511-4d60-975e-ead448ed5d13', 'eligibility', 'profile', 'bool',
            'subsidiary', 'borrower', 'subsidiary', 'bool',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            '56d4b239-8b5a-41f0-9e65-4ced292b0c0c', 'eligibility', 'profile', 'other',
            'company_name', 'borrower', 'companyName', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            '7518eade-0825-4464-8b07-c372fd69300c', 'eligibility', 'profile', 'other',
            'activity_street', 'borrower', 'addressStreet', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'ffa27a62-e831-4d9f-bdda-d7dbdd5ab57f', 'eligibility', 'profile', 'other',
            'activity_post_code', 'borrower', 'addressPostCode', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'e5c18fa8-adb2-4123-ad49-d7c6b95eae70', 'eligibility', 'profile', 'other',
            'activity_city', 'borrower', 'addressCity', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'c4876798-0ed5-4808-9ef9-c1810b158c4f', 'eligibility', 'profile', 'other',
            'activity_department', 'borrower', 'addressDepartment', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            '932afe50-582a-462c-b5cc-16cdd3f09c07', 'eligibility', 'profile', 'list',
            'activity_country', 'borrower', 'addressCountry', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'd61a4e71-4438-46f1-b1a5-376f98566c06', 'eligibility', 'profile', 'other',
            'activity_start_date', 'borrower', 'activityStartDate', 'DateTimeImmutable',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'f6ea8c30-48d1-4852-9c4a-5e1298f7f902', 'eligibility', 'profile', 'other',
            'siret', 'borrower', 'siret', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            '093a2142-ab5d-4b57-afb0-e8749131740b', 'eligibility', 'profile', 'other',
            'tax_number', 'borrower', 'taxNumber', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'eef6e5ac-8de6-4084-a06b-dd2974141d94', 'eligibility', 'profile', 'list',
            'legal_form', 'borrower', 'legalForm', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, '["SARL","SAS","SASU","EURL","SA","SELAS"]'
        ),
        (
            '7cccbd98-6b99-4425-8f29-83a04027740c', 'eligibility', 'profile', 'list',
            'company_naf_code', 'borrower', 'companyNafCode', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            '6c067265-5ff5-49f4-84f0-e511a4a7d42e', 'eligibility', 'profile', 'other',
            'employees_number', 'borrower', 'employeesNumber', 'int',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, 'person', NULL
        ),
        (
            '406628f8-26a4-44a9-9742-074f86b313e2', 'eligibility', 'profile', 'list',
            'exploitation_size', 'borrower', 'exploitationSize', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'fd5af2b2-81e7-44f4-a349-51d00e8e104b', 'eligibility', 'profile', 'other',
            'turnover', 'borrower', 'turnover', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, 'money', NULL
        ),
        (
            '938c689e-bddb-42b9-b84a-a00b18523e4f', 'eligibility', 'profile', 'other',
            'total_assets', 'borrower', 'totalAssets', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 1, 'money', NULL
        ),

        (
            '386f841e-3771-4a35-a54a-e4169fd80d63', 'eligibility', 'project', 'bool',
            'receiving_grant', 'project', 'receivingGrant', 'bool',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            'be7be094-9b78-4d97-adaa-d08d1edaec67', 'eligibility', 'project', 'other',
            'investment_street', 'project', 'addressStreet', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            '833b9e3a-c958-4792-adc1-41b05332f965', 'eligibility', 'project', 'other',
            'investment_post_code', 'project', 'addressPostCode', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            '91fc4bb3-0b1d-4d0d-ba46-54564c30a775', 'eligibility', 'project', 'other',
            'investment_city', 'project', 'addressCity', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            'c904c2fb-6940-49ef-b9c3-9961c38ef70e', 'eligibility', 'project', 'other',
            'investment_department', 'project', 'addressDepartment', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            '674d1e2d-cf35-4c05-9ee6-69a5bbe698d6', 'eligibility', 'project', 'list',
            'investment_country', 'project', 'addressCountry', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            '23892bef-00b0-4df5-981e-32913e708a2b', 'eligibility', 'project', 'list',
            'investment_thematic', 'project', 'investmentThematic', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            'ee365095-5e4e-4c02-bb45-b71506cbc42b', 'eligibility', 'project', 'list',
            'investment_type', 'project', 'investmentType', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            '5b621a54-17a8-4226-8251-ef8bc35c0aae', 'eligibility', 'project', 'list',
            'aid_intensity', 'project', 'aidIntensity', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            'c7b28186-59e6-4032-8abc-144f8c89e6db', 'eligibility', 'project', 'list',
            'additional_guaranty', 'project', 'additionalGuaranty', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            'd2b8441d-c2ef-4b11-b01e-ed145232995b', 'eligibility', 'project', 'list',
            'agricultural_branch', 'project', 'agriculturalBranch', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            '3dbe7d2c-2b78-4f72-ab52-ca3703e39f5b', 'eligibility', 'project', 'other',
            'project_total_amount', 'project', 'fundingMoney', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),
        (
            '58fd1213-d881-494e-abbc-3dfddb672370', 'eligibility', 'project', 'other',
            'project_contribution', 'project', 'contribution', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),
        (
            '3a14f624-43c5-4b44-9cec-6128298df493', 'eligibility', 'project', 'other',
            'eligible_fei_credit', 'project', 'eligibleFeiCredit', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),
        (
            'a7d8d27a-67d4-4add-90c9-76a787451ca2', 'eligibility', 'project', 'other',
            'total_fei_credit', 'project', 'totalFeiCredit', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),
        (
            '29a1d576-44f7-4bf7-8a20-4c354a4206ab', 'eligibility', 'project', 'other',
            'tangible_fei_credit', 'project', 'tangibleFeiCredit', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),
        (
            '838516c5-82a2-40b7-b8f8-d9fc7890f923', 'eligibility', 'project', 'other',
            'intangible_fei_credit', 'project', 'intangibleFeiCredit', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),
        (
            'd00e3272-d334-45d2-a93a-1078e6356537', 'eligibility', 'project', 'other',
            'credit_excluding_fei', 'project', 'creditExcludingFei', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),
        (
            'ff0d7855-bdfb-448f-b1b5-ef32e4b87c60', 'eligibility', 'project', 'other',
            'project_grant', 'project', 'grant', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),
        (
            'fa5dbf8e-ad12-46f4-b2dd-bb12cfbb77ae', 'eligibility', 'project', 'other',
            'land_value', 'project', 'landValue', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 1, 'money', NULL
        ),

        (
            'b7da24f1-4d1c-426e-9e25-ef2773113d2a', 'eligibility', 'loan', 'bool',
            'supporting_generations_renewal', 'financingObjects', 'supportingGenerationsRenewal', 'bool',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            '8cb4b512-fa4c-4638-9a28-11d16b450459', 'eligibility', 'loan', 'list',
            'financing_object_type', 'financingObjects', 'financingObjectType', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            '58acfc84-3d39-4a9d-98bd-acd884a0e74b', 'eligibility', 'loan', 'list',
            'loan_naf_code', 'financingObjects', 'loanNafCode', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            '55a1b77f-1b2d-40a8-8f77-a4fa2ae5f292', 'eligibility', 'loan', 'other',
            'bfr_value', 'financingObjects', 'bfrValue', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 1, 'money', NULL
        ),
        (
            '675056b4-49bb-40a7-bafe-9bcc86ad7b99', 'eligibility', 'loan', 'list',
            'loan_type', 'financingObjects', 'loanType', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, '["term_loan","short_term","revolving_credit","stand_by","signature_commitment"]'
        ),
        (
            'dc41f2c0-0ca6-4ac4-8d92-c9f583b97923', 'eligibility', 'loan', 'other',
            'loan_money', 'financingObjects', 'loanMoney', 'Money',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            'e9861e5b-0513-4c7b-9799-2f5a7dc267a4', 'eligibility', 'loan', 'other',
            'loan_duration', 'financingObjects', 'loanDuration', 'int',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 1, 'month', null
        ),
        (
            'f6f933e3-8164-430d-a670-390d0fc48311', 'eligibility', 'loan', 'other',
            'loan_deferral', 'financingObjects', 'loanDeferral', 'int',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 1, 'month', NULL
        ),
        (
            '93319782-8cfd-474f-bfe8-ab5aae88456b', 'eligibility', 'loan', 'list',
            'loan_periodicity', 'financingObjects', 'loanPeriodicity', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, '["monthly","quarterly","semi_annually","annually"]'
        ),
        (
            '61ad5da2-1ae1-4c0b-b3bd-ce42fc0bea3b', 'eligibility', 'loan', 'list',
            'investment_location', 'financingObjects', 'investmentLocation', 'ProgramChoiceOption',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),

        (
            'a11265ea-983c-4c5d-8518-feb163972288', 'info', 'program', 'other',
            'program_currency', 'program', 'funds.currency', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL
        ),
        (
            'dec52b3a-c544-48cc-9c0a-f118d7f056c5', 'info', 'program', 'other',
            'guaranty_duration', 'program', 'guarantyDuration', 'int',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL
        ),
        (
            'c1d1f43c-4f1b-4d07-92cd-03e2c898cda9', 'info', 'program', 'bool',
            'esb_calculation_activated', 'program', 'esbCalculationActivated', 'bool',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL
        ),
        (
            'cb808233-64ca-40a0-8cbb-a2cb07193325', 'info', 'program', 'bool',
            'loan_released_on_invoice', 'program', 'loanReleasedOnInvoice', 'bool',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL
        ),
        (
            'ebdcd939-65bd-4882-8fa9-4afdf933cd22', 'info', 'program', 'other',
            'max_fei_credit', 'program', 'maxFeiCredit', 'NullableMoney',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL
        ),
        (
            '53e4fee4-dee8-4620-9a75-85127b139fae', 'info', 'program', 'other',
            'rating_model', 'program', 'ratingModel', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Program', 0, NULL, NULL
        ),
        (
            '798d4095-a599-4e14-b0ad-6e06e5edb55f', 'info', 'reservation', 'other',
            'reservation_name', 'name', '', 'string',
            '', 0, NULL, NULL
        ),
        (
            '78465f81-b2b0-4fa4-a708-23556a9fda39', 'info', 'reservation', 'other',
            'reservation_status', 'currentStatus', '', 'int',
            '', 0, NULL, NULL
        ),
        (
            '17faacdb-448a-4a82-befb-ea1b3d5f83a3', 'info', 'reservation', 'other',
            'reservation_creation_date', 'added', '', 'DateTimeImmutable',
            '', 0, NULL, NULL
        ),
        (
            'd35bcf2a-aaaa-4c97-8eba-e7336589049a', 'info', 'reservation', 'other',
            'reservation_refusal_date', 'refusedByManagingCompanyDate', '', 'DateTimeImmutable',
            '', 0, NULL, NULL
        ),
        (
            'a7948dc1-eda2-4d92-a4f8-9646b5a0e41d', 'info', 'reservation', 'other',
            'reservation_signing_date', 'signingDate', '', 'DateTimeImmutable',
            '', 0, NULL, NULL
        ),
        (
            '0fa045c5-e153-4bf4-804c-e257a89a26d5', 'info', 'reservation', 'other',
            'reservation_managing_company', 'managingCompany', 'displayName', 'string',
            'KLS\\Core\\Entity\\Company', 0, NULL, NULL
        ),
        (
            '13a46517-8654-4b73-aa67-8b48ce5da334', 'info', 'profile', 'other',
            'borrower_type_grade', 'borrower', 'grade', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Borrower', 0, NULL, NULL
        ),
        (
            'e42a92b1-9fd0-48a5-b164-e3a7c191f429', 'info', 'project', 'other',
            'project_detail', 'project', 'detail', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        ),
        (
            '2fc96208-9fbe-40db-a04f-09825dae23a0', 'info', 'loan', 'other',
            'financing_object_name', 'financingObjects', 'name', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            '91c636cc-cb6a-48f2-ad4a-589b588ad441', 'info', 'loan', 'other',
            'loan_money_after_contractualisation', 'financingObjects', 'loanMoneyAfterContractualisation', 'NullableMoney',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            '6a5fb99d-60cc-483b-a39f-bb3a7a7825f1', 'info', 'loan', 'bool',
            'main_loan', 'financingObjects', 'mainLoan', 'bool',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            'de474438-9ffe-40c5-aedd-6bdb5f145354', 'info', 'loan', 'other',
            'loan_number', 'financingObjects', 'loanNumber', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            '762ca5ca-9266-4f9a-bade-02e04ff5614e', 'info', 'loan', 'other',
            'loan_operation_number', 'financingObjects', 'operationNumber', 'string',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            '495c6d49-f87c-40c8-a0d3-2af1cae770fb', 'info', 'loan', 'other',
            'first_release_date', 'financingObjects', 'firstReleaseDate', 'DateTimeImmutable',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),

        (
            '0c16807e-01e9-41b8-aa45-d20d9f7381c3', 'imported', 'loan', 'other',
            'loan_new_maturity', 'financingObjects', 'newMaturity', 'int',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),
        (
            '8d4fc763-4df3-4af2-8bce-21eb11363988', 'imported', 'loan', 'other',
            'loan_remaining_capital', 'financingObjects', 'remainingCapital', 'NullableMoney',
            'KLS\\CreditGuaranty\\FEI\\Entity\\FinancingObject', 0, NULL, NULL
        ),

        (
            'a9ef5697-5e3f-482c-a42a-2dd52277fd76', 'calcul', 'project', 'other',
            'total_gross_subsidy_equivalent', 'project', 'totalGrossSubsidyEquivalent', 'MoneyInterface',
            'KLS\\CreditGuaranty\\FEI\\Entity\\Project', 0, NULL, NULL
        );
        INSERT_FIELDS;
}
