<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\Traits\{LendingChargeable, Timestampable};

/**
 * @ORM\Entity
 */
class BidFee
{
    use LendingChargeable;
    use Timestampable;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Bids
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Bids", inversedBy="bidFees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_bid", referencedColumnName="id_bid", nullable=false)
     * })
     */
    private $bid;

    /**
     * @return int
     */
    public function getId(): int
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
}
