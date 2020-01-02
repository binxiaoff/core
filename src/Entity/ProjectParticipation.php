<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\{Fee, Money, NullableMoney};
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableTrait};

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "projectParticipation:read",
 *         "projectParticipationContact:read",
 *         "projectParticipationFee:read",
 *         "projectParticipationOffer:read",
 *         "projectOrganizer:read",
 *         "company:read",
 *         "role:read",
 *         "fee:read",
 *         "nullableMoney:read",
 *         "trancheOffer:read",
 *         "money:read",
 *         "lendingRate:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "projectParticipation:write",
 *         "projectParticipationFee:write",
 *         "projectParticipationOffer:read",
 *         "role:write",
 *         "fee:write",
 *         "nullableMoney:write"
 *     }},
 *     collectionOperations={
 *         "get": {"normalization_context": {"groups": {
 *             "projectParticipation:list",
 *             "projectParticipation:read",
 *             "projectParticipationContact:read",
 *             "projectParticipationFee:read",
 *             "projectParticipationOffer:read",
 *             "projectOrganizer:read",
 *             "company:read",
 *             "role:read",
 *             "fee:read",
 *             "nullableMoney:read"
 *         }}},
 *         "post": {
 *             "denormalization_context": {"groups": {
 *                 "projectParticipation:create",
 *                 "projectParticipation:write",
 *                 "projectParticipationFee:write",
 *                 "projectParticipationOffer:read",
 *                 "role:write",
 *                 "fee:write",
 *                 "nullableMoney:write"
 *             }},
 *             "security_post_denormalize": "is_granted('edit', object.getProject())"
 *         }
 *     },
 *     itemOperations={
 *         "get",
 *         "delete": {"security": "is_granted('edit', object.getProject())"},
 *         "patch": {"security": "is_granted('edit', object.getProject())"}
 *     }
 * )
 * @ApiFilter("Unilend\Filter\ArrayFilter", properties={"roles"})
 * @ApiFilter("Unilend\Filter\CountFilter", properties={"project.projectParticipations.projectParticipationOffers"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter", properties={"project.currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter", properties={"project.currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter", properties={"project.hash": "exact"})
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectParticipationRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"project", "company"})
 */
class ProjectParticipation
{
    use TimestampableTrait;
    use BlamableAddedTrait;

    private const STATUS_NOT_CONSULTED = 0;
    private const STATUS_CONSULTED     = 10;
    private const STATUS_UNINTERESTED  = 20;

    private const DEFAULT_STATUS = self::STATUS_NOT_CONSULTED;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Groups({"projectParticipation:read"})
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectParticipations")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     *
     * @Groups({"projectParticipation:read", "projectParticipation:create"})
     *
     * @MaxDepth(1)
     *
     * @Assert\NotBlank
     */
    private $project;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"projectParticipation:read", "projectParticipation:create"})
     *
     * @Assert\NotBlank
     */
    private $company;

    /**
     * @var ProjectParticipationContact[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationContact", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"projectParticipation:read"})
     */
    private $projectParticipationContacts;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     *
     * @Groups({"projectParticipation:read"})
     *
     * @Assert\NotBlank
     */
    private $currentStatus = self::DEFAULT_STATUS;

    /**
     * @var ProjectParticipationFee
     *
     * @ORM\OneToOne(targetEntity="ProjectParticipationFee", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipationFee;

    /**
     * @var ProjectParticipationOffer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ProjectParticipationOffer", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"projectParticipation:read"})
     */
    private $projectParticipationOffers;

    /**
     * Property created in order that API platform understand it's a nullable field.
     *
     * @var Fee|null
     *
     * @Groups({"projectParticipation:read", "projectParticipation:write"})
     */
    private $fee;

    /**
     * @var NullableMoney
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney", columnPrefix="invitation_")
     *
     * @Groups({"projectParticipation:read", "projectParticipation:write"})
     */
    private $invitationMoney;

    /**
     * @param Companies          $company
     * @param Project            $project
     * @param Clients            $addedBy
     * @param NullableMoney|null $invitationMoney
     *
     * @throws Exception
     */
    public function __construct(
        Companies $company,
        Project $project,
        Clients $addedBy,
        NullableMoney $invitationMoney = null
    ) {
        $this->added                      = new DateTimeImmutable();
        $this->addedBy                    = $addedBy;
        $this->company                    = $company;
        $this->project                    = $project;
        $this->invitationMoney            = $invitationMoney ?? new NullableMoney();
        $this->projectParticipationOffers = new ArrayCollection();

        $this->projectParticipationContacts = $company->getStaff()
            ->filter(static function (Staff $staff) use ($project) {
                return null === $project->getMarketSegment() || $staff->getMarketSegments()->contains($project->getMarketSegment());
            })
            ->map(function (Staff $staff) use ($addedBy) {
                return new ProjectParticipationContact($this, $staff->getClient(), $addedBy);
            })
        ;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project|null $project
     *
     * @return ProjectParticipation
     */
    public function setProject(?Project $project): ProjectParticipation
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Companies
     */
    public function getCompany(): Companies
    {
        return $this->company;
    }

    /**
     * @return bool
     *
     * @Groups({"projectParticipation:read"})
     */
    public function hasOffer(): bool
    {
        return 0 < count($this->projectParticipationOffers);
    }

    /**
     * @return bool
     *
     * @Groups({"projectParticipation:read"})
     */
    public function hasValidatedOffer(): bool
    {
        return 0 < count(
            $this->projectParticipationOffers->filter(
                static function (ProjectParticipationOffer $participationOffer) {
                    return $participationOffer->isAccepted();
                }
            )
        );
    }

    /**
     * @return ArrayCollection|ProjectParticipationOffer[]
     */
    public function getProjectParticipationOffers()
    {
        return $this->projectParticipationOffers;
    }

    /**
     * @Groups({"projectParticipation:read"})
     *
     * @return bool
     */
    public function isNotInterested(): bool
    {
        return $this->currentStatus === static::STATUS_UNINTERESTED && !$this->hasOffer();
    }

    /**
     * @return bool
     *
     * @Groups({"projectParticipation:read"})
     */
    public function isConsulted(): bool
    {
        return $this->currentStatus >= static::STATUS_CONSULTED;
    }

    /**
     * @return ProjectParticipation
     */
    public function setUninterested(): ProjectParticipation
    {
        if ($this->hasOffer()) {
            throw new DomainException('It is impossible to refuse after making an offer');
        }

        $this->currentStatus = static::STATUS_UNINTERESTED;

        return $this;
    }

    /**
     * @return ProjectParticipation
     */
    public function setConsulted(): ProjectParticipation
    {
        $this->currentStatus = ($this->currentStatus === static::STATUS_NOT_CONSULTED) ?
            static::STATUS_CONSULTED : $this->currentStatus;

        return $this;
    }

    /**
     * @return ProjectParticipationContact[]|ArrayCollection
     */
    public function getProjectParticipationContacts(): iterable
    {
        return $this->projectParticipationContacts;
    }

    /**
     * @return ProjectParticipationFee|null
     */
    public function getProjectParticipationFee(): ?ProjectParticipationFee
    {
        return $this->projectParticipationFee;
    }

    /**
     * @return NullableMoney|null
     */
    public function getInvitationMoney(): ?NullableMoney
    {
        return $this->invitationMoney->isValid() ? $this->invitationMoney : null;
    }

    /**
     * @param NullableMoney $nullableMoney
     *
     * @return NullableMoney
     */
    public function setInvitationMoney(NullableMoney $nullableMoney): NullableMoney
    {
        return $this->invitationMoney = $nullableMoney;
    }

    /**
     * @throws Exception
     *
     * @return Money
     *
     * @Groups({"projectParticipation:read"})
     */
    public function getOfferMoney(): Money
    {
        $money = new Money($this->getProject()->getGlobalFundingMoney()->getCurrency());

        foreach ($this->getProjectParticipationOffers() as $projectParticipationOffer) {
            $money = $money->add($projectParticipationOffer->getOfferMoney());
        }

        return $money;
    }

    /**
     * @return Fee
     */
    public function getFee(): ?Fee
    {
        return $this->getProjectParticipationFee() ? $this->getProjectParticipationFee()->getFee() : null;
    }

    /**
     * @param Fee|null $fee
     *
     * @throws Exception
     *
     * @return ProjectParticipation
     */
    public function setFee(?Fee $fee): ProjectParticipation
    {
        if (null === $fee) {
            $this->setProjectParticipationFee(null);

            return $this;
        }

        $projectParticipationFee = $this->getProjectParticipationFee();

        if (!$projectParticipationFee) {
            $projectParticipationFee = new ProjectParticipationFee($this, $fee);
        }

        $projectParticipationFee->getFee()->setRate($fee->getRate());
        $projectParticipationFee->getFee()->setType($fee->getType());
        $projectParticipationFee->getFee()->setComment($fee->getComment());
        $projectParticipationFee->getFee()->setRecurring($fee->isRecurring());

        return $this->setProjectParticipationFee($projectParticipationFee);
    }

    /**
     * @param ProjectParticipationFee|null $projectParticipationFee
     *
     * @return $this
     */
    public function setProjectParticipationFee(?ProjectParticipationFee $projectParticipationFee): ProjectParticipation
    {
        $this->projectParticipationFee = $projectParticipationFee;

        return $this;
    }

    /**
     * @return string|null
     *
     * @Groups({"projectParticipation:read"})
     */
    public function getOfferComment(): ?string
    {
        return $this->projectParticipationOffers->first() ? $this->projectParticipationOffers->first()->getComment() : null;
    }

    /**
     * @return string|null
     *
     * @Groups({"projectParticipation:read"})
     */
    public function getOfferCommitteeStatus(): ?string
    {
        return $this->projectParticipationOffers->first() ? $this->projectParticipationOffers->first()->getCommitteeStatus() : null;
    }

    /**
     * @return DateTimeImmutable|null
     *
     * @Groups({"projectParticipation:read"})
     */
    public function getOfferExpectedCommitteeDate(): ?DateTimeImmutable
    {
        return $this->projectParticipationOffers->first() ? $this->projectParticipationOffers->first()->getExpectedCommitteeDate() : null;
    }
}
