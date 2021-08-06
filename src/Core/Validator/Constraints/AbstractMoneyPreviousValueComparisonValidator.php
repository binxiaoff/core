<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Unilend\Core\Entity\Interfaces\MoneyInterface;

abstract class AbstractMoneyPreviousValueComparisonValidator extends AbstractPreviousValueComparisonValidator
{
    protected function checkPreconditions($value, Constraint $constraint): void
    {
        if (false === $value instanceof MoneyInterface) {
            throw new UnexpectedTypeException($value, MoneyInterface::class);
        }
    }

    protected function getPreviousValue($previousEntity, $value): MoneyInterface
    {
        $propertyPath = $this->context->getPropertyPath();
        $moneyClass   = \get_class($value);

        return new $moneyClass($previousEntity[$propertyPath . '.currency'], $previousEntity[$propertyPath . '.amount']);
    }
}
