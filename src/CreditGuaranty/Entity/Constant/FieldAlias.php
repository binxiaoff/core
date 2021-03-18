<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\Constant;

use Unilend\Core\Entity\Constant\AbstractEnum;

class FieldAlias extends AbstractEnum
{
    // User defined list
    public const BORROWER_TYPE                     = 'borrower_type';
    public const INVESTMENT_THEMATIC               = 'investment_thematic';
    public const FUNDING_OBJECT                    = 'funding_object';
    public const NAF_CODE                          = 'naf_code';

    // Pre-defined list
    public const LEGAL_FORM                        = 'legal_form';
    public const LOAN_TYPE                         = 'loan_type';
    public const ACTIVITY_COUNTRY                  = 'activity_country';

    // number
    public const EMPLOYEES_NUMBER                  = 'employees_number';
    public const LAST_YEAR_TURNOVER                = 'last_year_turnover';
    public const LAST_FIVE_YEAR_TURNOVER           = 'last_year_turnover';
    public const TOTAL_BALANCE                     = 'total_balance';
    public const GRANT_AMOUNT                      = 'grant_amount';
    public const LOW_DENSITY_MEDICAL_AREA_EXERCISE = 'low_density_medical_are_exercise';

    // date
    public const FIRST_ACTIVITY_DATE               = 'first_activity_date';

    // Boolean fields
    public const JURIDICAL_PERSON                  = 'juridical_person';
    public const ON_GOING_CREATION                 = 'on_going_creation';
    public const RECEIVING_GRANT                   = 'receiving_grant';
    public const SUBSIDIARY                        = 'subsidiary';

    // Other
    public const COMPANY_NAME                      = 'company_name';
    public const COMPANY_ADDRESS                   = 'company_address';
    public const BORROWER_IDENTITY                 = 'borrower_identity';
    public const BENEFICIARY_ADDRESS               = 'beneficiary_address';
    public const TAX_NUMBER                        = 'tax_number';
    public const SIREN_CODE                        = 'siren_code';
    public const SIRET_CODE                        = 'siret_code';
}
