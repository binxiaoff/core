<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class BidPercentFee
{
    /**
     * @var PercentFee
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\PercentFee", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_percent_fee", referencedColumnName="id", nullable=false)
     * })
     */
    private $percentFee;

    /**
     * @var Bids
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Bids", inversedBy="bidPercentFees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_bid", referencedColumnName="id_bid", nullable=false)
     * })
     */
    private $bid;

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
     * @return BidPercentFee
     */
    public function setBid(Bids $bid): BidPercentFee
    {
        $this->bid = $bid;

        return $this;
    }

    /**
     * @return PercentFee|null
     */
    public function getPercentFee(): ?PercentFee
    {
        return $this->percentFee;
    }

    /**
     * @param PercentFee $percentFee
     *
     * @return BidPercentFee
     */
    public function setPercentFee(PercentFee $percentFee): BidPercentFee
    {
        $this->percentFee = $percentFee;

        return $this;
    }
}
