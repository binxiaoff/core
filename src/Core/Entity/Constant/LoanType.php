<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant;

final class LoanType extends AbstractEnum
{
    public const TERM_LOAN            = 'term_loan';
    public const SHORT_TERM           = 'short_term';
    public const REVOLVING_CREDIT     = 'revolving_credit';
    public const STAND_BY             = 'stand_by';
    public const SIGNATURE_COMMITMENT = 'signature_commitment';

    /**
     * @return string[]|array
     */
    public static function getChargeableLoadTypes(): array
    {
        return [
            self::SHORT_TERM,
            self::REVOLVING_CREDIT,
            self::STAND_BY,
        ];
    }
}
