<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class MoneyGreaterThanOrEqual extends Constraint
{
    public string $message = 'The money amount is not greater than the previous one.';
}
