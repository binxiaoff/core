<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\Constant;

use Unilend\Core\Entity\Constant\AbstractEnum;

/**
 * todo: this file can be delete ?
 */
class EligibilityFieldAlias extends AbstractEnum
{
    // User defined list
    public const BORROWER_TYPE       = 'borrower_type';
    public const INVESTMENT_THEMATIC = 'investment_thematic';
    public const FUNDING_OBJECT      = 'funding_object';
    public const NAF_CODE            = 'naf_code';
    // Pre-defined list
    public const LEGAL_FORM = 'legal_form';
    public const LOAN_TYPE  = 'loan_type';

    // Boolean fields
    public const JURIDICAL_PERSON  = 'juridical_person';
    public const ON_GOING_CREATION = 'on_going_creation';
    public const RECEIVING_GRANT   = 'receiving_grant';
    public const SUBSIDIARY        = 'subsidiary';

    // Other
    public const COMPANY_NAME        = 'company_name';
    public const COMPANY_ADDRESS     = 'company_address';
    public const BORROWER_IDENTITY   = 'borrower_identity';
    public const BENEFICIARY_ADDRESS = 'beneficiary_address';
    public const TAX_NUMBER          = 'tax_number';
}
