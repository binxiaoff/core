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
    private $minMoney;

    /**
     * @param NullableMoney      $money
     * @param NullableFee        $fee
     * @param NullableMoney|null $minMoney
     */
    public function __construct(NullableMoney $money, NullableFee $fee, ?NullableMoney $minMoney = null)
    {
        $this->minMoney = $minMoney;
        parent::__construct($money, $fee);
    }

    /**
     * @return NullableMoney
     */
    public function getMinMoney(): NullableMoney
    {
        return $this->minMoney;
    }
}
