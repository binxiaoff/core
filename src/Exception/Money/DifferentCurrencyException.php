<?php

declare(strict_types=1);

namespace Unilend\Exception\Money;

use InvalidArgumentException;
use Unilend\Entity\Interfaces\MoneyInterface;

class DifferentCurrencyException extends InvalidArgumentException
{
    /** @var MoneyInterface[] */
    private $moneys;

    /**
     * @param MoneyInterface ...$moneys
     */
    public function __construct(MoneyInterface ...$moneys)
    {
        $this->moneys = $moneys;
        parent::__construct('The given money object have different currencies', 152);
    }

    /**
     * @return MoneyInterface[]
     */
    public function getMoneys(): array
    {
        return $this->moneys;
    }
}
