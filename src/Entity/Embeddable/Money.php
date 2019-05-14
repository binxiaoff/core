<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class Money
{
    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=10, scale=2)
     *
     * @Assert\NotBlank
     */
    private $amount;

    /**
     * 3 letter ISO 4217 code (Currency code).
     *
     * @var string
     *
     * @ORM\Column(type="string", length=3)
     *
     * @Assert\NotBlank
     */
    private $currency;

    /**
     * @return string|null
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $amount
     *
     * @return Money
     */
    public function setAmount(string $amount): Money
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param string $currency
     *
     * @return Money
     */
    public function setCurrency(string $currency): Money
    {
        $this->currency = $currency;

        return $this;
    }
}
