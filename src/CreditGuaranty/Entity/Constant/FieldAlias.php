<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\Constant;

use Unilend\Core\Entity\Constant\AbstractEnum;

class FieldAlias extends AbstractEnum
{
    // User defined list
    public const BORROWER_TYPE       = 'borrower_type';
    public const INVESTMENT_THEMATIC = 'investment_thematic';
    public const FUNDING_OBJECT      = 'funding_object';
    public const NAF_CODE_PROJECT    = 'naf_code_project';
    public const NAF_CODE_COMPANY    = 'naf_code_company';
    public const LOAN_DURATION       = 'loan_duration';

    // Pre-defined list
    public const LEGAL_FORM       = 'legal_form';
    public const LOAN_TYPE        = 'loan_type';
    public const ACTIVITY_COUNTRY = 'activity_country';

    // number
    public const EMPLOYEES_NUMBER                  = 'employees_number';
    public const LAST_YEAR_TURNOVER                = 'last_year_turnover';
    public const LAST_FIVE_YEAR_TURNOVER           = '5_years_average_turnover';
    public const TOTAL_ASSETS                      = 'total_assets';
    public const GRANT_AMOUNT                      = 'grant_amount';
    public const LOW_DENSITY_MEDICAL_AREA_EXERCISE = 'low_density_medical_area_exercise';
    public const PROJECT_TOTAL_AMOUNT              = 'project_total_amount';
    public const FUNDING_OBJECT_AMOUNT             = 'funding_object_amount';
    public const LOAN_DEFERRAL                     = 'loan_deferral';

    // date
    public const ACTIVITY_START_DATE = 'activity_start_date';

    // Boolean fields
    public const JURIDICAL_PERSON         = 'juridical_person';
    public const ON_GOING_CREATION        = 'on_going_creation';
    public const RECEIVING_GRANT          = 'receiving_grant';
    public const SUBSIDIARY               = 'subsidiary';
    public const LOAN_RELEASED_ON_INVOICE = 'loan_released_on_invoice';

    // Other
    public const COMPANY_NAME        = 'company_name';
    public const COMPANY_ADDRESS     = 'company_address';
    public const BORROWER_IDENTITY   = 'borrower_identity';
    public const BENEFICIARY_ADDRESS = 'beneficiary_address';
    public const TAX_NUMBER          = 'tax_number';
    public const SIREN               = 'siren';
    public const SIRET               = 'siret';
}
