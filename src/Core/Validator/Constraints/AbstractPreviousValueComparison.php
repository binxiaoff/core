<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

abstract class AbstractPreviousValueComparison extends Constraint
{
    public string $message;
}
