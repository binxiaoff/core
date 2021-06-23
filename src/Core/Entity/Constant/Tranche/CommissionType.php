<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant\Tranche;

use Unilend\Core\Entity\Constant\AbstractEnum;

final class CommissionType extends AbstractEnum
{
    public const NON_UTILISATION = 'non_utilisation';
    public const COMMITMENT      = 'commitment';
}
