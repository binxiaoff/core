<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Constant\Tranche;

use KLS\Core\Entity\Constant\AbstractEnum;

final class CommissionType extends AbstractEnum
{
    public const NON_UTILISATION = 'non_utilisation';
    public const COMMITMENT      = 'commitment';
}
