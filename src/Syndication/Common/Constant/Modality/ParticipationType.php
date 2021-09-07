<?php

declare(strict_types=1);

namespace KLS\Syndication\Common\Constant\Modality;

use KLS\Core\Entity\Constant\AbstractEnum;

final class ParticipationType extends AbstractEnum
{
    public const DIRECT            = 'direct';
    public const SUB_PARTICIPATION = 'sub_participation';
}
