<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints\PreviousValue;

use Unilend\Core\Entity\Interfaces\MoneyInterface;
use Unilend\Core\Validator\Constraints\AbstractMoneyPreviousValueComparisonValidator;

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
