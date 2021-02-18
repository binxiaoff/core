<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Unilend\Core\Entity\Interfaces\MoneyInterface;

class MoneyNotResettableValidator extends AbstractMoneyPreviousValueComparisonValidator
{
    /**
     * @inheritDoc
     */
    protected function compareValues(MoneyInterface $value, MoneyInterface $previousValue): bool
    {
        return null !== $value->getAmount() || null === $previousValue->getAmount();
    }
}
