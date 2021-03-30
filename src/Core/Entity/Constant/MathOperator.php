<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Constant;

class MathOperator extends AbstractEnum
{
    public const INFERIOR          = 'lt';
    public const INFERIOR_OR_EQUAL = 'lte';
    public const EQUAL             = 'eq';
    public const SUPERIOR          = 'gt';
    public const SUPERIOR_OR_EQUAL = 'gte';
    public const BETWEEN           = 'bt';
}
