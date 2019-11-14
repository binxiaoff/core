<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @Groups({"project:create", "project:list", "projectParticipation:list"})
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
     * @Assert\Currency
     *
     * @Groups({"project:create", "project:list", "projectParticipation:list"})
     */
    private $currency;

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
     * @param Money $money
     *
     * @return Money
     */
    public function add(Money $money): Money
    {
        if ($money->getCurrency() !== $this->getCurrency()) {
            throw new InvalidArgumentException(sprintf('The currencies are different (%s and %s)', $this->getCurrency(), $money->getCurrency()));
        }

        return new Money(
            $this->currency,
            bcadd($this->amount, $money->amount, 2)
        );
    }
}
