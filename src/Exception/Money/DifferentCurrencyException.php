<?php

declare(strict_types=1);

namespace Unilend\Exception\Money;

use InvalidArgumentException;
use Unilend\Entity\Embeddable\Money;

class DifferentCurrencyException extends InvalidArgumentException
{
    /** @var Money[] */
    private $moneys;

    /**
     * @param Money ...$moneys
     */
    public function __construct(Money ...$moneys)
    {
        $this->moneys = $moneys;
        parent::__construct('The given money object have different currencies', 152);
    }

    /**
     * @return Money[]
     */
    public function getMoneys(): array
    {
        return $this->moneys;
    }
}
