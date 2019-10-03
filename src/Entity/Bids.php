<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Unilend\Entity\Traits\{BlamableAddedTrait, LendableTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedBid")
 *
 * @ORM\Table(name="bids", indexes={@ORM\Index(columns={"id_tranche", "status"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\AssociationOverrides({@ORM\AssociationOverride(name="tranche", inversedBy="bids")})
 */
class Bids
{
    use LendableTrait;
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;

    public const STATUS_PENDING  = 0;
    public const STATUS_ACCEPTED = 1;
    public const STATUS_REJECTED = 2;

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
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $comment;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     */
    private $projectedCommitteeDate;

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
     * @return string
     */
    public function getComment(): string
    {
        return (string) $this->comment;
    }

    /**
     * @param string $comment
     *
     * @return self
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getProjectedCommitteeDate(): ?DateTimeImmutable
    {
        return $this->projectedCommitteeDate;
    }

    /**
     * @param DateTimeImmutable $projectedCommitteeDate
     *
     * @return Bids
     */
    public function setProjectedCommitteeDate(?DateTimeImmutable $projectedCommitteeDate): Bids
    {
        $this->projectedCommitteeDate = $projectedCommitteeDate;

        return $this;
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
        $totalFeeRate = 0.0000;

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
        $totalFeeRate = 0.0000;

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
