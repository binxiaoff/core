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
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableTrait, TraceableBlamableUpdatedTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "post": {"security_post_denormalize": "is_granted('bid', object.getProjectParticipation())"}
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {"security": "is_granted('edit', object.getProjectParticipation().getProject())", "denormalization_context": {"groups": {"projectOffer:update"}}},
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
     * @Assert\Expression("this.getProjectParticipation().isBiddable() == true")
     *
     * @Groups({"project:list"})
     */
    private $projectParticipation;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:list", "project:view"})
     */
    private $committeeStatus;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:list", "project:view"})
     */
    private $expectedCommitteeDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:view"})
     */
    private $comment;

    /**
     * @var TrancheOffer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TrancheOffer", mappedBy="projectParticipationOffer", cascade={"persist"}, orphanRemoval=true)
     */
    private $trancheOffers;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $addedBy
     * @param Money|null           $offerMoney
     * @param string               $committeeStatus
     *
     * @throws Exception
     */
    public function __construct(
        ProjectParticipation $projectParticipation,
        Clients $addedBy,
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
     * @param string $comment
     *
     * @return ProjectParticipationOffer
     */
    public function setComment(string $comment): ProjectParticipationOffer
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
     * @Groups({"project:view"})
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
     */
    private function setOfferMoney(Money $offerMoney)
    {
        $this->getTrancheOffers()->clear();

        $syndicatedMoney = $this->getProjectParticipation()->getProject()->getTranchesTotalMoney();
        $remainderMoney  = clone $syndicatedMoney;

        foreach ($this->getProjectParticipation()->getProject()->getTranches() as $tranche) {
            $split          = $offerMoney->multiply($syndicatedMoney->ratio($tranche->getMoney()));
            $remainderMoney = $remainderMoney->substract($split);

            $this->trancheOffers->add(
                new TrancheOffer($this, $tranche, $split, $this->addedBy)
            );
        }

        /** @var TrancheOffer $lastTrancheOffer */
        $lastTrancheOffer = $this->getTrancheOffers()->last();
        $lastTrancheOffer->getMoney()->add($remainderMoney);
    }
}
