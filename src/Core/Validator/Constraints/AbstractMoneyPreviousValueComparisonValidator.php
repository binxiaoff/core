<?php

declare(strict_types=1);

namespace KLS\Core\Validator\Constraints;

use KLS\Core\Entity\Interfaces\MoneyInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

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
