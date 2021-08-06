<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

abstract class AbstractNumericPreviousValueComparisonValidator extends AbstractPreviousValueComparisonValidator
{
    protected function checkPreconditions($value, Constraint $constraint): void
    {
        if (false === \is_numeric($value)) {
            throw new UnexpectedTypeException($value, 'numeric');
        }
    }
}
