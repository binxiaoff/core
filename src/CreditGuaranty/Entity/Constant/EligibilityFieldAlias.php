<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\Constant;

use Unilend\Core\Entity\Constant\{AbstractEnum, LegalForm, Tranche\LoanType};

/**
 * For the convenient of development, the constants is synchronised with EligibilityCriteria
 * todo: Maybe the entity EligibilityCriteria is not necessary.
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

    /**
     * @return string[]
     */
    public static function getPredefinedListFields(): array
    {
        return [
            self::LEGAL_FORM => LegalForm::class,
            self::LOAN_TYPE => LoanType::class,
        ];
    }

    /**
     * @return string[]
     */
    public static function getListFields(): array
    {
        return [
            self::BORROWER_TYPE,
            self::INVESTMENT_THEMATIC,
            self::FUNDING_OBJECT,
            self::LEGAL_FORM,
            self::LOAN_TYPE,
        ];
    }
}
