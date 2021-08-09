<?php

declare(strict_types=1);

namespace KLS\Syndication\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Embeddable\NullableMoney;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Embeddable
 */
class RangedOfferWithFee extends OfferWithFee
{
    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"rangedOfferWithFee:read", "rangedOfferWithFee:write"})
     */
    private NullableMoney $maxMoney;

    /**
     * @throws Exception
     */
    public function __construct(?NullableMoney $money = null, ?string $feeRate = null, ?NullableMoney $maxMoney = null)
    {
        $this->maxMoney = $maxMoney ?? new NullableMoney();

        parent::__construct($money, $feeRate);
    }

    public function getMaxMoney(): NullableMoney
    {
        return $this->maxMoney;
    }
}
