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
     * @Groups({
     *     "project:create",
     *     "project:list",
     *     "project:view",
     *     "project:update",
     *     "money:read",
     *     "money:write"
     * })
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
     * @Groups({
     *     "project:create",
     *     "project:list",
     *     "project:view",
     *     "project:update",
     *     "money:read",
     *     "money:write"
     * })
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
     * @return float
     */
    public function ratio(Money $divisor): float
    {
        if ($divisor->getCurrency() !== $this->getCurrency()) {
            throw new DifferentCurrencyException($this, $divisor);
        }

        return (float) bcdiv($this->amount, (string) $divisor->getAmount(), 4);
    }

    /**
     * @param float $divisor
     *
     * @return Money
     */
    public function divide(float $divisor): Money
    {
        return new Money(
            $this->getCurrency(),
            $this->round(bcdiv($this->amount, (string) $divisor, 4))
        );
    }

    /**
     * @param mixed $factor
     *
     * @return Money
     */
    public function multiply(float $factor): Money
    {
        return new Money(
            $this->currency,
            $this->round(bcmul($this->amount, (string) $factor, 4))
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
            bcsub($this->amount, $subtrahend->amount, 2)
        );
    }

    /**
     * @param string $number
     *
     * @return string
     */
    private function round(string $number): string
    {
        return (string) round((float) $number, 2);
    }
}
