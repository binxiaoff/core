<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class BidFee
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
     * @var Bids
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Bids", inversedBy="bidFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_bid", referencedColumnName="id_bid", nullable=false)
     * })
     */
    private $bid;

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
     * @return Bids
     */
    public function getBid(): Bids
    {
        return $this->bid;
    }

    /**
     * @param Bids $bid
     *
     * @return BidFee
     */
    public function setBid(Bids $bid): BidFee
    {
        $this->bid = $bid;

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
     * @return BidFee
     */
    public function setFee(Fee $fee): BidFee
    {
        $this->fee = $fee;

        return $this;
    }
}
