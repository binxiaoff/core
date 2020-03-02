<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Embeddable\LendingRate;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableTrait, TraceableBlamableUpdatedTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedTrancheOffer")
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_tranche", "id_project_participation_offer"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TrancheOffer
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TraceableBlamableUpdatedTrait;

    public const STATUS_PENDED   = 'pended';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tranche", inversedBy="trancheOffers")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false)
     * })
     */
    private $tranche;

    /**
     * @var ProjectParticipationOffer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipationOffer", inversedBy="trancheOffers")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_participation_offer", nullable=false)
     * })
     */
    private $projectParticipationOffer;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"trancheOffer:read"})
     */
    private $status;

    /**
     * @var LendingRate
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\LendingRate")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"trancheOffer:read"})
     */
    private $rate;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Money")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"trancheOffer:read"})
     */
    private $money;

    /**
     * @var TrancheOfferFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TrancheOfferFee", mappedBy="trancheOffer", cascade={"persist"}, orphanRemoval=true)
     */
    private $trancheOfferFees;

    /**
     * @param ProjectParticipationOffer $projectOffer
     * @param Tranche                   $tranche
     * @param Money                     $money
     * @param Staff                     $addedBy
     * @param LendingRate|null          $rate
     * @param string                    $status
     *
     * @throws Exception
     */
    public function __construct(
        ProjectParticipationOffer $projectOffer,
        Tranche $tranche,
        Money $money,
        Staff $addedBy,
        LendingRate $rate = null,
        string $status = self::STATUS_PENDED
    ) {
        $this->projectParticipationOffer = $projectOffer;
        $this->tranche                   = $tranche;
        $this->money                     = $money;
        $this->status                    = $status;
        $this->rate                      = $rate ?? clone $tranche->getRate();
        $this->trancheOfferFees          = new ArrayCollection();
        $this->added                     = new DateTimeImmutable();
        $this->addedBy                   = $addedBy;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Tranche
     */
    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    /**
     * @param Tranche $tranche
     *
     * @return TrancheOffer
     */
    public function setTranche(Tranche $tranche): TrancheOffer
    {
        $this->tranche = $tranche;

        return $this;
    }

    /**
     * @return ProjectParticipationOffer
     */
    public function getProjectParticipationOffer(): ProjectParticipationOffer
    {
        return $this->projectParticipationOffer;
    }

    /**
     * @param ProjectParticipationOffer $projectOffer
     *
     * @return TrancheOffer
     */
    public function setProjectParticipationOffer(ProjectParticipationOffer $projectOffer): TrancheOffer
    {
        $this->projectParticipationOffer = $projectOffer;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return TrancheOffer
     */
    public function setStatus(string $status): TrancheOffer
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return LendingRate
     */
    public function getRate(): LendingRate
    {
        return $this->rate;
    }

    /**
     * @param LendingRate $rate
     *
     * @return TrancheOffer
     */
    public function setRate(LendingRate $rate): TrancheOffer
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }

    /**
     * @param Money $money
     *
     * @return TrancheOffer
     */
    public function setMoney(Money $money): TrancheOffer
    {
        $this->money = $money;

        return $this;
    }

    /**
     * @param TrancheOfferFee $trancheOfferFee
     *
     * @return TrancheOffer
     */
    public function addTrancheOfferFees(TrancheOfferFee $trancheOfferFee): TrancheOffer
    {
        $trancheOfferFee->setTrancheOffer($this);

        if (false === $this->trancheOfferFees->contains($trancheOfferFee)) {
            $this->trancheOfferFees->add($trancheOfferFee);
        }

        return $this;
    }

    /**
     * @param TrancheOfferFee $trancheOfferFee
     *
     * @return TrancheOffer
     */
    public function removeTrancheOfferFees(TrancheOfferFee $trancheOfferFee): TrancheOffer
    {
        if ($this->trancheOfferFees->contains($trancheOfferFee)) {
            $this->trancheOfferFees->removeElement($trancheOfferFee);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|TrancheOfferFee[]
     */
    public function getTrancheOfferFees(): Collection
    {
        return $this->trancheOfferFees;
    }

    /**
     * @return float
     */
    public function getOneTimeFeeTotalRate(): float
    {
        $totalFeeRate = 0.0000;

        foreach ($this->getTrancheOfferFees() as $trancheOfferFees) {
            if (false === $trancheOfferFees->getFee()->isRecurring()) {
                $totalFeeRate = round(bcadd($trancheOfferFees->getFee()->getRate(), $totalFeeRate, 3), 2);
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

        foreach ($this->getTrancheOfferFees() as $trancheOfferFees) {
            if ($trancheOfferFees->getFee()->isRecurring()) {
                $totalFeeRate = round(bcadd($trancheOfferFees->getFee()->getRate(), $totalFeeRate, 3), 2);
            }
        }

        return $totalFeeRate;
    }

    /**
     * @return array
     */
    public function getAvailableStatus(): array
    {
        return self::getConstants('STATUS_');
    }
}
