<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Embeddable
 */
class RangedOfferWithFee extends OfferWithFee
{
    /**
     * @var NullableMoney
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"rangedOfferWithFee:read", "rangedOfferWithFee:write"})
     */
    private $minMoney;

    /**
     * @param NullableMoney|null $money
     * @param string|null        $feeRate
     * @param NullableMoney|null $minMoney
     */
    public function __construct(?NullableMoney $money = null, ?string $feeRate = null, ?NullableMoney $minMoney = null)
    {
        $this->minMoney = $minMoney ?? new NullableMoney();

        parent::__construct($money, $feeRate);
    }

    /**
     * @return NullableMoney
     */
    public function getMinMoney(): NullableMoney
    {
        return $this->minMoney;
    }
}
