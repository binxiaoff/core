<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableTrait, TraceableBlamableUpdatedTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"projectParticipationOffer:read", "money:read", "trancheOffer:read", "lendingRate:read", "blameable:read"}},
 *     denormalizationContext={"groups": {"projectParticipationOffer:write", "money:write"}},
 *     collectionOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "post": {
 *             "security_post_denormalize": "is_granted('edit', object.getProjectParticipation())",
 *             "denormalization_context": {"groups": {"projectParticipationOffer:create", "projectParticipationOffer:write", "money:write"}}
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {"security": "is_granted('edit', object.getProjectParticipation())"},
 *     }
 * )
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectOffer")
 *
 * @ORM\Table
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectParticipationOffer
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TraceableBlamableUpdatedTrait;

    public const COMMITTEE_STATUS_PENDED   = 'pended';
    public const COMMITTEE_STATUS_ACCEPTED = 'accepted';
    public const COMMITTEE_STATUS_REJECTED = 'rejected';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var ProjectParticipation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectParticipation", inversedBy="projectParticipationOffers")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_participation", nullable=false)
     * })
     *
     * @Groups({"projectParticipationOffer:create"})
     */
    private $projectParticipation;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipationOffer:read", "projectParticipationOffer:write"})
     */
    private $committeeStatus;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipationOffer:read", "projectParticipationOffer:write"})
     */
    private $expectedCommitteeDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipationOffer:read", "projectParticipationOffer:write"})
     */
    private $comment;

    /**
     * @var TrancheOffer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TrancheOffer", mappedBy="projectParticipationOffer", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"projectParticipationOffer:read"})
     */
    private $trancheOffers;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $addedBy
     * @param Money|null           $offerMoney
     * @param string               $committeeStatus
     *
     * @throws Exception
     */
    public function __construct(
        ProjectParticipation $projectParticipation,
        Staff $addedBy,
        Money $offerMoney = null,
        string $committeeStatus = self::COMMITTEE_STATUS_PENDED
    ) {
        $this->projectParticipation = $projectParticipation;
        $this->committeeStatus      = $committeeStatus;
        $this->trancheOffers        = new ArrayCollection();
        $this->added                = new DateTimeImmutable();
        $this->addedBy              = $addedBy;

        if ($offerMoney) {
            $this->setOfferMoney($offerMoney);
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return ProjectParticipation
     */
    public function getProjectParticipation(): ProjectParticipation
    {
        return $this->projectParticipation;
    }

    /**
     * @return string
     */
    public function getCommitteeStatus(): string
    {
        return $this->committeeStatus;
    }

    /**
     * @param string $committeeStatus
     *
     * @return ProjectParticipationOffer
     */
    public function setCommitteeStatus(string $committeeStatus): ProjectParticipationOffer
    {
        $this->committeeStatus = $committeeStatus;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getExpectedCommitteeDate(): ?DateTimeImmutable
    {
        return $this->expectedCommitteeDate;
    }

    /**
     * @param DateTimeImmutable $expectedCommitteeDate
     *
     * @return ProjectParticipationOffer
     */
    public function setExpectedCommitteeDate(?DateTimeImmutable $expectedCommitteeDate): ProjectParticipationOffer
    {
        $this->expectedCommitteeDate = $expectedCommitteeDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return (string) $this->comment;
    }

    /**
     * @param string|null $comment
     *
     * @return ProjectParticipationOffer
     */
    public function setComment(?string $comment): ProjectParticipationOffer
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return ArrayCollection|TrancheOffer[]
     */
    public function getTrancheOffers()
    {
        return $this->trancheOffers;
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @return ProjectParticipationOffer
     */
    public function addTrancheOffer(TrancheOffer $trancheOffer): ProjectParticipationOffer
    {
        $trancheOffer->setProjectParticipationOffer($this);

        if (false === $this->trancheOffers->contains($trancheOffer)) {
            $this->trancheOffers->add($trancheOffer);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableCommitteeStatus(): array
    {
        return self::getConstants('COMMITTEE_STATUS_');
    }

    /**
     * @throws Exception
     *
     * @return Embeddable\Money
     *
     * @Groups({"projectParticipationOffer:read"})
     */
    public function getOfferMoney(): Money
    {
        $money = new Money($this->getProjectParticipation()->getProject()->getGlobalFundingMoney()->getCurrency());

        foreach ($this->getTrancheOffers() as $trancheOffer) {
            $money = $money->add($trancheOffer->getMoney());
        }

        return $money;
    }

    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        // TODO replace afterwards
        return true;
    }

    /**
     * @param Money $offerMoney
     *
     * @throws Exception
     *
     * @return ProjectParticipationOffer
     *
     * @Groups({"projectParticipationOffer:write"})
     */
    public function setOfferMoney(Money $offerMoney): ProjectParticipationOffer
    {
        $totalMoney     = $this->getProjectParticipation()->getProject()->getTranchesTotalMoney();
        $remainderMoney = clone $offerMoney;

        foreach ($this->getProjectParticipation()->getProject()->getTranches() as $tranche) {
            $ratio          = $tranche->getMoney()->ratio($totalMoney);
            $split          = $offerMoney->multiply($ratio);
            $remainderMoney = $remainderMoney->substract($split);

            $trancheOffer = $this->getTrancheOffer($tranche);

            $trancheOffer instanceof TrancheOffer ?
                $trancheOffer->setMoney($split)
                : $this->trancheOffers->add(
                    new TrancheOffer($this, $tranche, $split, $this->addedBy)
                );
        }

        /** @var TrancheOffer $lastTrancheOffer */
        $lastTrancheOffer = $this->getTrancheOffers()->last();
        $lastTrancheOffer->setMoney($lastTrancheOffer->getMoney()->add($remainderMoney));

        return $this;
    }

    /**
     * @param Tranche $tranche
     *
     * @return TrancheOffer|null
     */
    private function getTrancheOffer(Tranche $tranche): ?TrancheOffer
    {
        return $this->trancheOffers->filter(
            static function (TrancheOffer $trancheOffer) use ($tranche) {
                return $tranche === $trancheOffer->getTranche();
            }
        )->first() ?: null;
    }
}
