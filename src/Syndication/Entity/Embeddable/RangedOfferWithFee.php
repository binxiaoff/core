<?php

declare(strict_types=1);

namespace Unilend\Syndication\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Embeddable\NullableMoney;

/**
 * @ORM\Embeddable
 */
class RangedOfferWithFee extends OfferWithFee
{
    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"rangedOfferWithFee:read", "rangedOfferWithFee:write"})
     */
    private NullableMoney $maxMoney;

    /**
     * @param NullableMoney|null $money
     * @param string|null        $feeRate
     * @param NullableMoney|null $maxMoney
     *
     * @throws Exception
     */
    public function __construct(?NullableMoney $money = null, ?string $feeRate = null, ?NullableMoney $maxMoney = null)
    {
        $this->maxMoney = $maxMoney ?? new NullableMoney();

        parent::__construct($money, $feeRate);
    }

    /**
     * @return NullableMoney
     */
    public function getMaxMoney(): NullableMoney
    {
        return $this->maxMoney;
    }
}
