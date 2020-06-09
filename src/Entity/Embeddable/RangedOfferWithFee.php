<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Embeddable
 */
class RangedOfferWithFee extends OfferWithFee
{
    /**
     * @var NullableMoney|null
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"rangedOfferWithFee:read", "rangedOfferWithFee:write"})
     */
    protected $minMoney;

    /**
     * @param NullableMoney $minMoney
     * @param NullableMoney $money
     * @param Fee           $fee
     */
    public function __construct(NullableMoney $minMoney, NullableMoney $money, Fee $fee)
    {
        $this->minMoney = $minMoney;
        parent::__construct($money, $fee);
    }

    /**
     * @return Money|null
     */
    public function getMinMoney(): ?Money
    {
        return $this->minMoney;
    }
}
