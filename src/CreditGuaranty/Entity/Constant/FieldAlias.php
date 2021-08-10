<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Entity\Constant;

use KLS\Core\Entity\Constant\AbstractEnum;

/**
 * There is a convention that the name of the target class property should be the camel case form of the related constant value in this class.
 * For exemple, the target property of self::BORROWER_TYPE should be Borrower::borrowerType.
 */
class FieldAlias extends AbstractEnum
{
    // User-defined list
    public const ACTIVITY_COUNTRY      = 'activity_country';
    public const ADDITIONAL_GUARANTY   = 'additional_guaranty';
    public const AGRICULTURAL_BRANCH   = 'agricultural_branch';
    public const AID_INTENSITY         = 'aid_intensity';
    public const BORROWER_TYPE         = 'borrower_type';
    public const COMPANY_NAF_CODE      = 'company_naf_code';
    public const EXPLOITATION_SIZE     = 'exploitation_size';
    public const FINANCING_OBJECT_TYPE = 'financing_object_type';
    public const INVESTMENT_COUNTRY    = 'investment_country';
    public const INVESTMENT_LOCATION   = 'investment_location';
    public const INVESTMENT_THEMATIC   = 'investment_thematic';
    public const INVESTMENT_TYPE       = 'investment_type';
    public const LOAN_NAF_CODE         = 'loan_naf_code';

    // Pre-defined list
    public const LEGAL_FORM       = 'legal_form';
    public const LOAN_PERIODICITY = 'loan_periodicity';
    public const LOAN_TYPE        = 'loan_type';

    // Boolean
    public const CREATION_IN_PROGRESS           = 'creation_in_progress';
    public const RECEIVING_GRANT                = 'receiving_grant';
    public const SUBSIDIARY                     = 'subsidiary';
    public const SUPPORTING_GENERATIONS_RENEWAL = 'supporting_generations_renewal';
    public const YOUNG_FARMER                   = 'young_farmer';

    // Other
    public const ACTIVITY_STREET       = 'activity_street';
    public const ACTIVITY_POST_CODE    = 'activity_post_code';
    public const ACTIVITY_CITY         = 'activity_city';
    public const ACTIVITY_DEPARTMENT   = 'activity_department';
    public const BENEFICIARY_NAME      = 'beneficiary_name';
    public const COMPANY_NAME          = 'company_name';
    public const INVESTMENT_STREET     = 'investment_street';
    public const INVESTMENT_POST_CODE  = 'investment_post_code';
    public const INVESTMENT_CITY       = 'investment_city';
    public const INVESTMENT_DEPARTMENT = 'investment_department';
    public const SIRET                 = 'siret';
    public const TAX_NUMBER            = 'tax_number';

    // number
    public const BFR_VALUE             = 'bfr_value';
    public const EMPLOYEES_NUMBER      = 'employees_number';
    public const LAND_VALUE            = 'land_value';
    public const LOAN_DEFERRAL         = 'loan_deferral';
    public const LOAN_DURATION         = 'loan_duration';
    public const PROJECT_CONTRIBUTION  = 'project_contribution';
    public const PROJECT_GRANT         = 'project_grant';
    public const PROJECT_TOTAL_AMOUNT  = 'project_total_amount';
    public const ELIGIBLE_FEI_CREDIT   = 'eligible_fei_credit';
    public const CREDIT_EXCLUDING_FEI  = 'credit_excluding_fei';
    public const TANGIBLE_FEI_CREDIT   = 'tangible_fei_credit';
    public const INTANGIBLE_FEI_CREDIT = 'intangible_fei_credit';
    public const TOTAL_ASSETS          = 'total_assets';
    public const TOTAL_FEI_CREDIT      = 'total_fei_credit';
    public const TURNOVER              = 'turnover';

    // date
    public const ACTIVITY_START_DATE = 'activity_start_date';

    public const PROGRAM_CHOICE_OPTION_FIELDS = [
        self::ACTIVITY_COUNTRY,
        self::ADDITIONAL_GUARANTY,
        self::AGRICULTURAL_BRANCH,
        self::AID_INTENSITY,
        self::BORROWER_TYPE,
        self::COMPANY_NAF_CODE,
        self::EXPLOITATION_SIZE,
        self::FINANCING_OBJECT_TYPE,
        self::INVESTMENT_COUNTRY,
        self::INVESTMENT_LOCATION,
        self::INVESTMENT_THEMATIC,
        self::INVESTMENT_TYPE,
        self::LEGAL_FORM,
        self::LOAN_NAF_CODE,
        self::LOAN_PERIODICITY,
        self::LOAN_TYPE,
    ];
}
