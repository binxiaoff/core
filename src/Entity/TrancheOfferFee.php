<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TrancheOfferFee
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Fee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Fee")
     */
    private $fee;

    /**
     * @var TrancheOffer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\TrancheOffer", inversedBy="trancheOfferFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche_offer", nullable=false)
     * })
     */
    private $trancheOffer;

    /**
     * Initialise some object-value.
     */
    public function __construct()
    {
        $this->fee = new Fee();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TrancheOffer
     */
    public function getTrancheOffer(): TrancheOffer
    {
        return $this->trancheOffer;
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @return TrancheOfferFee
     */
    public function setTrancheOffer(TrancheOffer $trancheOffer): TrancheOfferFee
    {
        $this->trancheOffer = $trancheOffer;

        return $this;
    }

    /**
     * @return Fee|null
     */
    public function getFee(): ?Fee
    {
        return $this->fee;
    }

    /**
     * @param Fee $fee
     *
     * @return TrancheOfferFee
     */
    public function setFee(Fee $fee): TrancheOfferFee
    {
        $this->fee = $fee;

        return $this;
    }
}
