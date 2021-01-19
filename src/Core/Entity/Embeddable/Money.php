<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Interfaces\MoneyInterface;

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
