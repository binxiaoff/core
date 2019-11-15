<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Unilend\Entity\Embeddable\LendingRate;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableTrait, TraceableBlamableUpdatedTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedTrancheOffer")
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_tranche", "id_project_offer"})})
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
     * @var ProjectOffer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectOffer", inversedBy="trancheOffers")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_offer", nullable=false)
     * })
     */
    private $projectOffer;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Gedmo\Versioned
     */
    private $status;

    /**
     * @var LendingRate
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\LendingRate")
     *
     * @Gedmo\Versioned
     */
    private $rate;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Money")
     *
     * @Gedmo\Versioned
     */
    private $money;

    /**
     * @var TrancheOfferFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="TrancheOfferFee", mappedBy="trancheOffer", cascade={"persist"}, orphanRemoval=true)
     */
    private $trancheOfferFees;

    /**
     * @param ProjectOffer     $projectOffer
     * @param Tranche          $tranche
     * @param Money            $money
     * @param Clients          $addedby
     * @param LendingRate|null $rate
     * @param string           $status
     *
     * @throws Exception
     */
    public function __construct(
        ProjectOffer $projectOffer,
        Tranche $tranche,
        Money $money,
        Clients $addedby,
        LendingRate $rate = null,
        string $status = self::STATUS_PENDED
    ) {
        $this->projectOffer     = $projectOffer;
        $this->tranche          = $tranche;
        $this->money            = $money;
        $this->status           = $status;
        $this->rate             = $rate ?? clone $tranche->getRate();
        $this->trancheOfferFees = new ArrayCollection();
        $this->added            = new DateTimeImmutable();
        $this->addedBy          = $addedby;
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
     * @return ProjectOffer
     */
    public function getProjectOffer(): ProjectOffer
    {
        return $this->projectOffer;
    }

    /**
     * @param ProjectOffer $projectOffer
     *
     * @return TrancheOffer
     */
    public function setProjectOffer(ProjectOffer $projectOffer): TrancheOffer
    {
        $this->projectOffer = $projectOffer;

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
