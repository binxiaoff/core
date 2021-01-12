<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant;

use Unilend\Core\Traits\ConstantsAwareTrait;

class ParticipationType
{
    use ConstantsAwareTrait;

    public const DIRECT            = 'direct';
    public const SUB_PARTICIPATION = 'sub_participation';

    /**
     * Is private to forbid instanciation
     */
    private function __construct()
    {
    }

    /**
     * @return string[]|array
     */
    public static function getParticipationTypes(): array
    {
        return static::getConstants();
    }
}
