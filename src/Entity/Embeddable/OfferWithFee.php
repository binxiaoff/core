<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Embeddable
 */
class OfferWithFee extends Offer
{
    /**
     * @var NullableSimplifiedFee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableSimplifiedFee")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"offerWithFee:read", "offerWithFee:write"})
     */
    protected $fee;

    /**
     * @param NullableMoney         $money
     * @param NullableSimplifiedFee $fee
     */
    public function __construct(NullableMoney $money, NullableSimplifiedFee $fee)
    {
        $this->fee = $fee;
        parent::__construct($money);
    }

    /**
     * @return NullableSimplifiedFee
     */
    public function getFee(): NullableSimplifiedFee
    {
        return $this->fee;
    }
}
