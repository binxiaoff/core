<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Constant;

final class LoanType extends AbstractEnum
{
    public const TERM_LOAN            = 'term_loan';
    public const SHORT_TERM           = 'short_term';
    public const LONG_TERM            = 'long_term';
    public const ACTION               = 'action';
    public const OBLIGATION           = 'obligation';
    public const OBSA                 = 'obsa';
    public const OCA                  = 'oca';
    public const CROWD_FUNDING        = 'crowd_funding';
    public const MEZZANINE            = 'mezzanine';
    public const REVOLVING_CREDIT     = 'revolving_credit';
    public const STAND_BY             = 'stand_by';
    public const SIGNATURE_COMMITMENT = 'signature_commitment';

    /**
     * @return string[]|array
     */
    public static function getChargeableLoanTypes(): array
    {
        return [
            self::SHORT_TERM,
            self::REVOLVING_CREDIT,
            self::STAND_BY,
        ];
    }
}
