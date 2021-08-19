<?php

declare(strict_types=1);

namespace KLS\Syndication\Common\Constant\Tranche;

use KLS\Core\Entity\Constant\AbstractEnum;

final class CommissionType extends AbstractEnum
{
    public const NON_UTILISATION = 'non_utilisation';
    public const COMMITMENT      = 'commitment';
}
