<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Exception\Money\DifferentCurrencyException;

/**
 * @ORM\Embeddable
 */
class Money
{
    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=15, scale=2)
     *
     * @Assert\NotBlank
     * @Assert\Type("numeric")
     * @Assert\Positive
     *
     * @Groups({"project:create", "project:list", "project:update", "project:view", "tranche:create", "tranche:update", "projectParticipation:list", "projectParticipation:create"})
     */
    protected $amount;

    /**
     * 3 letter ISO 4217 code (Currency code).
     *
     * @var string
     *
     * @ORM\Column(type="string", length=3)
     *
     * @Assert\NotBlank
     * @Assert\Currency
     *
     * @Groups({"project:create", "project:list", "project:view", "project:update", "tranche:create", "tranche:update", "projectParticipation:list", "projectParticipation:create"})
     */
    protected $currency;

    /**
     * @param string $currency
     * @param string $amount
     */
    public function __construct(string $currency, string $amount = '0')
    {
        $this->currency = $currency;
        $this->amount   = $amount;
    }

    /**
     * the type hint is nullable because the child classes have this property nullable.
     *
     * @return string
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * the type hint is nullable because the child classes have this property nullable.
     *
     * @return string
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param Money $addend
     *
     * @return Money
     */
    public function add(Money $addend): Money
    {
        if ($addend->getCurrency() !== $this->getCurrency()) {
            throw new DifferentCurrencyException($this, $addend);
        }

        return new Money(
            $this->currency,
            bcadd($this->amount, $addend->amount, 2)
        );
    }

    /**
     * @param mixed $divisor
     *
     * @return Money
     */
    public function divide($divisor)
    {
        if ($divisor instanceof Money) {
            if ($divisor->getCurrency() !== $this->getCurrency()) {
                throw new DifferentCurrencyException($this, $divisor);
            }
            $divisor = $divisor->getAmount();
        }

        return new Money(
            $this->currency,
            bcdiv($this->amount, (string) $divisor, 2)
        );
    }

    /**
     * @param mixed $factor
     *
     * @return Money
     */
    public function multiply($factor)
    {
        if ($factor instanceof Money) {
            if ($factor->getCurrency() !== $this->getCurrency()) {
                throw new DifferentCurrencyException($this, $factor);
            }
            $factor = $factor->getAmount();
        }

        return new Money(
            $this->currency,
            bcdiv($this->amount, (string) $factor, 2)
        );
    }

    /**
     * @param Money $subtrahend
     *
     * @return Money
     */
    public function substract(Money $subtrahend): Money
    {
        if ($subtrahend->getCurrency() !== $this->getCurrency()) {
            throw new DifferentCurrencyException($this, $subtrahend);
        }

        return new Money(
            $this->currency,
            bcadd($this->amount, $subtrahend->amount, 2)
        );
    }
}
