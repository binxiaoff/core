<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\{LendableTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(name="bids", indexes={@ORM\Index(columns={"id_tranche", "status"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\BidsRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\AssociationOverrides({@ORM\AssociationOverride(name="tranche", inversedBy="bids")})
 */
class Bids
{
    use LendableTrait;
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const STATUS_PENDING                      = 0;
    public const STATUS_ACCEPTED                     = 1;
    public const STATUS_REJECTED                     = 2;
    public const STATUS_TEMPORARILY_REJECTED_AUTOBID = 3;

    /**
     * @var Autobid|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Autobid")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_autobid", referencedColumnName="id_autobid")
     * })
     */
    private $autobid;

    /**
     * @var int|null
     *
     * @ORM\Column(name="ordre", type="integer", nullable=true)
     */
    private $ordre;

    /**
     * @var int
     *
     * @ORM\Column(name="id_bid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBid;

    /**
     * @var AcceptedBids[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\AcceptedBids", mappedBy="bid", cascade={"persist"}, orphanRemoval=true)
     */
    private $acceptedBids;

    /**
     * @var BidFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BidFee", mappedBy="bid", cascade={"persist"}, orphanRemoval=true)
     */
    private $bidFees;

    /**
     * Bids constructor.
     */
    public function __construct()
    {
        $this->acceptedBids = new ArrayCollection();
        $this->bidFees      = new ArrayCollection();
        $this->traitInit();
    }

    /**
     * @param Autobid|null $autobid
     *
     * @return Bids
     */
    public function setAutobid(?Autobid $autobid): Bids
    {
        $this->autobid = $autobid;

        return $this;
    }

    /**
     * @return Autobid|null
     */
    public function getAutobid(): ?Autobid
    {
        return $this->autobid;
    }

    /**
     * @param int|null $ordre
     *
     * @return Bids
     */
    public function setOrdre(?int $ordre): Bids
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    /**
     * @return int|null
     */
    public function getIdBid(): ?int
    {
        return $this->idBid;
    }

    /**
     * @return ArrayCollection|AcceptedBids[]
     */
    public function getAcceptedBids(): iterable
    {
        return $this->acceptedBids;
    }

    /**
     * @param BidFee $bidFee
     *
     * @return Bids
     */
    public function addBidFee(BidFee $bidFee): Bids
    {
        $bidFee->setBid($this);

        if (false === $this->bidFees->contains($bidFee)) {
            $this->bidFees->add($bidFee);
        }

        return $this;
    }

    /**
     * @param BidFee $bidFee
     *
     * @return Bids
     */
    public function removeBidFee(BidFee $bidFee): Bids
    {
        if ($this->bidFees->contains($bidFee)) {
            $this->bidFees->removeElement($bidFee);
        }

        return $this;
    }

    /**
     * @return iterable|BidFee[]
     */
    public function getBidFees(): iterable
    {
        return $this->bidFees;
    }

    /**
     * @return float
     */
    public function getOneTimeFeeTotalRate(): float
    {
        $totalFeeRate = 0.00;

        foreach ($this->getBidFees() as $bidFee) {
            if (false === $bidFee->getFee()->isRecurring()) {
                $totalFeeRate = round(bcadd($bidFee->getFee()->getRate(), $totalFeeRate, 3), 2);
            }
        }

        return $totalFeeRate;
    }

    /**
     * @return float
     */
    public function getRecurringFeeTotalRate(): float
    {
        $totalFeeRate = 0.00;

        foreach ($this->getBidFees() as $bidFee) {
            if ($bidFee->getFee()->isRecurring()) {
                $totalFeeRate = round(bcadd($bidFee->getFee()->getRate(), $totalFeeRate, 3), 2);
            }
        }

        return $totalFeeRate;
    }

    /**
     * @return array
     */
    public function getAllStatus(): array
    {
        return self::getConstants('STATUS_');
    }
}
