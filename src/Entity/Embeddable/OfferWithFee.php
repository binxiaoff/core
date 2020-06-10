<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Embeddable
 */
class OfferWithFee extends Offer
{
    /**
     * @var NullableFee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableFee")
     *
     * @Groups({"offerWithFee:read", "offerWithFee:write"})
     */
    protected $fee;

    /**
     * @param NullableMoney $money
     * @param NullableFee   $fee
     */
    public function __construct(NullableMoney $money, NullableFee $fee)
    {
        $this->fee = $fee;
        parent::__construct($money);
    }

    /**
     * @return NullableFee
     */
    public function getFee(): NullableFee
    {
        return $this->fee;
    }
}
