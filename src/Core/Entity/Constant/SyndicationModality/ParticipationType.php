<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant\SyndicationModality;

use Unilend\Core\Entity\Constant\AbstractEnum;

final class ParticipationType extends AbstractEnum
{
    public const DIRECT            = 'direct';
    public const SUB_PARTICIPATION = 'sub_participation';
}
