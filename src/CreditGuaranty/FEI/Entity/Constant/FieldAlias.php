<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity\Constant;

use KLS\Core\Entity\Constant\AbstractEnum;

class FieldAlias extends AbstractEnum
{
    //
    // Eligibility field aliases
    //

    // User-defined list
    public const ACTIVITY_COUNTRY      = 'activity_country';
    public const ACTIVITY_DEPARTMENT   = 'activity_department';
    public const ADDITIONAL_GUARANTY   = 'additional_guaranty';
    public const AGRICULTURAL_BRANCH   = 'agricultural_branch';
    public const AID_INTENSITY         = 'aid_intensity';
    public const BORROWER_TYPE         = 'borrower_type';
    public const COMPANY_NAF_CODE      = 'company_naf_code';
    public const EXPLOITATION_SIZE     = 'exploitation_size';
    public const FINANCING_OBJECT_TYPE = 'financing_object_type';
    public const INVESTMENT_COUNTRY    = 'investment_country';
    public const INVESTMENT_DEPARTMENT = 'investment_department';
    public const INVESTMENT_LOCATION   = 'investment_location';
    public const INVESTMENT_THEMATIC   = 'investment_thematic';
    public const INVESTMENT_TYPE       = 'investment_type';
    public const LEGAL_FORM            = 'legal_form';
    public const LOAN_NAF_CODE         = 'loan_naf_code';
    public const LOAN_TYPE             = 'loan_type';
    public const PRODUCT_CATEGORY_CODE = 'product_category_code';
    public const TARGET_TYPE           = 'target_type';

    // Pre-defined list
    public const LOAN_PERIODICITY = 'loan_periodicity';

    // Boolean
    public const BENEFITING_PROFIT_TRANSFER                        = 'benefiting_profit_transfer';
    public const CREATION_IN_PROGRESS                              = 'creation_in_progress';
    public const ECONOMICALLY_VIABLE                               = 'economically_viable';
    public const IN_NON_COOPERATIVE_JURISDICTION                   = 'in_non_cooperative_jurisdiction';
    public const LISTED_ON_STOCK_MARKET                            = 'listed_on_stock_market';
    public const LOAN_ALLOWED_REFINANCE_RESTRUCTURE                = 'loan_allowed_refinance_restructure';
    public const LOAN_SUPPORTING_DOCUMENTS_DATES_AFTER_APPLICATION = 'loan_supporting_documents_dates_after_application';
    public const PROJECT_RECEIVED_FEAGA_OCM_FUNDING                = 'project_received_feaga_ocm_funding';
    public const RECEIVING_GRANT                                   = 'receiving_grant';
    public const SUBJECT_OF_RESTRUCTURING_PLAN                     = 'subject_of_restructuring_plan';
    public const SUBJECT_OF_UNPERFORMED_RECOVERY_ORDER             = 'subject_of_unperformed_recovery_order';
    public const SUBSIDIARY                                        = 'subsidiary';
    public const SUPPORTING_GENERATIONS_RENEWAL                    = 'supporting_generations_renewal';
    public const TRANSACTION_AFFECTED                              = 'transaction_affected';
    public const YOUNG_FARMER                                      = 'young_farmer';

    // Other
    public const ACTIVITY_CITY        = 'activity_city';
    public const ACTIVITY_POST_CODE   = 'activity_post_code';
    public const ACTIVITY_STREET      = 'activity_street';
    public const BENEFICIARY_NAME     = 'beneficiary_name';
    public const COMPANY_NAME         = 'company_name';
    public const INVESTMENT_CITY      = 'investment_city';
    public const INVESTMENT_POST_CODE = 'investment_post_code';
    public const INVESTMENT_STREET    = 'investment_street';
    public const REGISTRATION_NUMBER  = 'registration_number';
    // number
    public const BFR_VALUE             = 'bfr_value';
    public const CREDIT_EXCLUDING_FEI  = 'credit_excluding_fei';
    public const ELIGIBLE_FEI_CREDIT   = 'eligible_fei_credit';
    public const EMPLOYEES_NUMBER      = 'employees_number';
    public const INTANGIBLE_FEI_CREDIT = 'intangible_fei_credit';
    public const LAND_VALUE            = 'land_value';
    public const LOAN_DEFERRAL         = 'loan_deferral';
    public const LOAN_DURATION         = 'loan_duration';
    public const LOAN_MONEY            = 'loan_money';
    public const PROJECT_CONTRIBUTION  = 'project_contribution';
    public const PROJECT_GRANT         = 'project_grant';
    public const PROJECT_TOTAL_AMOUNT  = 'project_total_amount';
    public const TANGIBLE_FEI_CREDIT   = 'tangible_fei_credit';
    public const TOTAL_ASSETS          = 'total_assets';
    public const TOTAL_FEI_CREDIT      = 'total_fei_credit';
    public const TURNOVER              = 'turnover';
    // date
    public const ACTIVITY_START_DATE = 'activity_start_date';

    public const PROGRAM_CHOICE_OPTION_FIELDS = [
        self::ACTIVITY_COUNTRY,
        self::ACTIVITY_DEPARTMENT,
        self::ADDITIONAL_GUARANTY,
        self::AGRICULTURAL_BRANCH,
        self::AID_INTENSITY,
        self::BORROWER_TYPE,
        self::COMPANY_NAF_CODE,
        self::EXPLOITATION_SIZE,
        self::FINANCING_OBJECT_TYPE,
        self::INVESTMENT_COUNTRY,
        self::INVESTMENT_DEPARTMENT,
        self::INVESTMENT_LOCATION,
        self::INVESTMENT_TYPE,
        self::LEGAL_FORM,
        self::LOAN_NAF_CODE,
        self::LOAN_PERIODICITY,
        self::LOAN_TYPE,
        self::PRODUCT_CATEGORY_CODE,
        self::TARGET_TYPE,
    ];

    public const NAF_NACE_FIELDS = [
        self::COMPANY_NAF_CODE => 'company_nace_code',
        self::LOAN_NAF_CODE    => 'loan_nace_code',
    ];

    public const CREATION_IN_PROGRESS_RELATED_FIELDS = [
        self::ACTIVITY_START_DATE,
        self::REGISTRATION_NUMBER,
    ];

    public const ESB_RELATED_FIELDS = [
        self::AID_INTENSITY,
        self::LOAN_DURATION,
        self::LOAN_MONEY,
        self::PROJECT_GRANT,
        self::TOTAL_FEI_CREDIT,
    ];

    public const RECEIVING_GRANT_RELATED_FIELDS = [
        self::PROJECT_GRANT,
    ];

    //
    // Reporting field aliases
    //

    // Info
    public const BORROWER_TYPE_GRADE                 = 'borrower_type_grade';
    public const ESB_CALCULATION_ACTIVATED           = 'esb_calculation_activated';
    public const FINANCING_OBJECT_NAME               = 'financing_object_name';
    public const LOAN_MONEY_AFTER_CONTRACTUALISATION = 'loan_money_after_contractualisation';
    public const LOAN_NUMBER                         = 'loan_number';
    public const LOAN_OPERATION_NUMBER               = 'loan_operation_number';
    public const LOAN_RELEASED_ON_INVOICE            = 'loan_released_on_invoice';
    public const MAIN_LOAN                           = 'main_loan';
    public const MAX_FEI_CREDIT                      = 'max_fei_credit';
    public const PROGRAM_CURRENCY                    = 'program_currency';
    public const PROGRAM_DURATION                    = 'program_duration';
    public const PROJECT_DETAIL                      = 'project_detail';
    public const RATING_MODEL                        = 'rating_model';
    public const RESERVATION_MANAGING_COMPANY        = 'reservation_managing_company';
    public const RESERVATION_NAME                    = 'reservation_name';
    public const RESERVATION_STATUS                  = 'reservation_status';
    // date
    public const FIRST_RELEASE_DATE         = 'first_release_date';
    public const REPORTING_FIRST_DATE       = 'reporting_first_date';
    public const REPORTING_LAST_DATE        = 'reporting_last_date';
    public const REPORTING_VALIDATION_DATE  = 'reporting_validation_date';
    public const RESERVATION_CREATION_DATE  = 'reservation_creation_date';
    public const RESERVATION_EXCLUSION_DATE = 'reservation_exclusion_date';
    public const RESERVATION_SIGNING_DATE   = 'reservation_signing_date';

    public const DATE_FIELDS = [
        self::ACTIVITY_START_DATE,
        self::FIRST_RELEASE_DATE,
        self::REPORTING_FIRST_DATE,
        self::REPORTING_LAST_DATE,
        self::REPORTING_VALIDATION_DATE,
        self::RESERVATION_CREATION_DATE,
        self::RESERVATION_SIGNING_DATE,
    ];

    public const MAPPING_REPORTING_DATES = [
        FieldAlias::REPORTING_FIRST_DATE      => 'reportingFirstDate',
        FieldAlias::REPORTING_LAST_DATE       => 'reportingLastDate',
        FieldAlias::REPORTING_VALIDATION_DATE => 'reportingValidationDate',
    ];

    // Imported
    public const LOAN_NEW_MATURITY      = 'loan_new_maturity';
    public const LOAN_REMAINING_CAPITAL = 'loan_remaining_capital';

    // Calcul
    public const TOTAL_GROSS_SUBSIDY_EQUIVALENT = 'total_gross_subsidy_equivalent';

    public const VIRTUAL_FIELDS = [
        self::RECEIVING_GRANT,
        self::RESERVATION_EXCLUSION_DATE,
        self::TOTAL_GROSS_SUBSIDY_EQUIVALENT,
    ];
}
