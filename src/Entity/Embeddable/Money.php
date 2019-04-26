<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Money
{
    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $amount;

    /**
     * 3 letter ISO 4217 code (Currency code).
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $currency;

    /**
     * Money constructor.
     *
     * @param string $amount
     * @param string $currency
     */
    public function __construct(string $amount, string $currency)
    {
        $this->amount   = $amount;
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}
