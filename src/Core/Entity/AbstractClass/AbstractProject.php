<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\AbstractClass;

use Unilend\Core\Traits\ConstantsAwareTrait;

abstract class AbstractProject
{
    use ConstantsAwareTrait;

    public const INTERNAL_RATING_SCORE_A_PLUS  = 'A+';
    public const INTERNAL_RATING_SCORE_A       = 'A';
    public const INTERNAL_RATING_SCORE_B_PLUS  = 'B+';
    public const INTERNAL_RATING_SCORE_B       = 'B';
    public const INTERNAL_RATING_SCORE_C_PLUS  = 'C+';
    public const INTERNAL_RATING_SCORE_C       = 'C';
    public const INTERNAL_RATING_SCORE_C_MINUS = 'C-';
    public const INTERNAL_RATING_SCORE_D_PLUS  = 'D+';
    public const INTERNAL_RATING_SCORE_D       = 'D';
    public const INTERNAL_RATING_SCORE_D_MINUS = 'D-';
    public const INTERNAL_RATING_SCORE_E_PLUS  = 'E+';
    public const INTERNAL_RATING_SCORE_E       = 'E';
    public const INTERNAL_RATING_SCORE_E_MINUS = 'E-';
    public const INTERNAL_RATING_SCORE_F       = 'F';
    public const INTERNAL_RATING_SCORE_Z       = 'Z';

    public const FUNDING_SPECIFICITY_FSA = 'FSA';
    public const FUNDING_SPECIFICITY_LBO = 'LBO';

    /**
     * @return array
     */
    public function getInternalRatingScores(): array
    {
        return self::getConstants('INTERNAL_RATING_SCORE_');
    }
}
