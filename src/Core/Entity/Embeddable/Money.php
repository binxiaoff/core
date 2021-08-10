<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Interfaces\MoneyInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable
 */
class Money implements MoneyInterface
{
    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=15, scale=2)
     *
     * @Assert\NotBlank
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Groups({"money:read", "money:write"})
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
     * @Groups({"money:read", "money:write"})
     */
    protected $currency;

    public function __construct(string $currency, string $amount = '0')
    {
        $this->currency = $currency;
        $this->amount   = $amount;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
