<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\Constant;

use Unilend\Core\Entity\Constant\AbstractEnum;

/**
 * There is a convention that the name of the target class property should be the camel case form of the related constant value in this class.
 * For exemple, the target property of self::BORROWER_TYPE should be Borrower::borrowerType.
 */
class FieldAlias extends AbstractEnum
{
    // User-defined list
    public const BORROWER_TYPE       = 'borrower_type';
    public const COMPANY_NAF_CODE    = 'company_naf_code';
    public const EXPLOITATION_SIZE   = 'exploitation_size';
    public const FINANCING_OBJECT    = 'financing_object';
    public const INVESTMENT_THEMATIC = 'investment_thematic';
    public const PROJECT_NAF_CODE    = 'project_naf_code';

    // Pre-defined list
    public const ACTIVITY_COUNTRY = 'activity_country';
    public const LEGAL_FORM       = 'legal_form';
    public const LOAN_TYPE        = 'loan_type';

    // Boolean
    public const CREATION_IN_PROGRESS     = 'creation_in_progress';
    public const LOAN_RELEASED_ON_INVOICE = 'loan_released_on_invoice';
    public const RECEIVING_GRANT          = 'receiving_grant';
    public const SUBSIDIARY               = 'subsidiary';
    public const YOUNG_FARMER             = 'young_farmer';

    // Other
    public const ACTIVITY_STREET     = 'activity_street';
    public const ACTIVITY_POST_CODE  = 'activity_post_code';
    public const ACTIVITY_CITY       = 'activity_city';
    public const ACTIVITY_DEPARTMENT = 'activity_department';
    public const BENEFICIARY_NAME    = 'beneficiary_name';
    public const COMPANY_NAME        = 'company_name';
    public const SIRET               = 'siret';
    public const TAX_NUMBER          = 'tax_number';

    // number
    public const EMPLOYEES_NUMBER     = 'employees_number';
    public const LOAN_AMOUNT          = 'loan_amount';
    public const LOAN_DEFERRAL        = 'loan_deferral';
    public const LOAN_DURATION        = 'loan_duration';
    public const PROJECT_TOTAL_AMOUNT = 'project_total_amount';
    public const TOTAL_ASSETS         = 'total_assets';
    public const TURNOVER             = 'turnover';

    // date
    public const ACTIVITY_START_DATE = 'activity_start_date';

    public const PROGRAM_CHOICE_OPTION_FIELDS = [
        self::ACTIVITY_COUNTRY,
        self::BORROWER_TYPE,
        self::COMPANY_NAF_CODE,
        self::EXPLOITATION_SIZE,
        self::FINANCING_OBJECT,
        self::INVESTMENT_THEMATIC,
        self::LEGAL_FORM,
        self::LOAN_TYPE,
        self::PROJECT_NAF_CODE,
    ];
}
