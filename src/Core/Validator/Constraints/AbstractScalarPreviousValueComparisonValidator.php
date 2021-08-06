<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

abstract class AbstractScalarPreviousValueComparisonValidator extends AbstractPreviousValueComparisonValidator
{
    /**
     * {@inheritDoc}
     */
    protected function checkPreconditions($value, Constraint $constraint): void
    {
        if (\is_object($value)) {
            throw new UnexpectedTypeException($value, 'non object');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreviousValue(array $previousEntity, $value)
    {
        $propertyPath = $this->context->getPropertyPath();

        return $previousEntity[$propertyPath];
    }
}
