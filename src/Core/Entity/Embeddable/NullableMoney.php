<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Interfaces\MoneyInterface;

/**
 * @ORM\Embeddable
 */
class NullableMoney implements MoneyInterface
{
    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Gedmo\Versioned
     *
     * @Groups({"nullableMoney:read", "nullableMoney:write"})
     */
    protected ?string $amount;

    /**
     * 3 letter ISO 4217 code (Currency code).
     *
     * @ORM\Column(type="string", length=3, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Currency
     *
     * @Gedmo\Versioned
     *
     * @Groups({"nullableMoney:read", "nullableMoney:write"})
     */
    protected ?string $currency;

    public function __construct(?string $currency = null, ?string $amount = null)
    {
        $this->amount   = $amount;
        $this->currency = $currency;
    }

    public function isValid(): bool
    {
        return $this->currency && $this->amount;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function isNull(): bool
    {
        return null === $this->amount || null === $this->currency;
    }
}
