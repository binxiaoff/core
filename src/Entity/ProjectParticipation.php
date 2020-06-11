<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource, ApiSubresource};
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\{Fee, Money, NullableMoney};
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "projectParticipation:read",
 *         "projectParticipationContact:read",
 *         "projectParticipationFee:read",
 *         "projectParticipationOffer:read",
 *         "projectOrganizer:read",
 *         "company:read",
 *         "fee:read",
 *         "nullableMoney:read",
 *         "trancheOffer:read",
 *         "money:read",
 *         "lendingRate:read",
 *         "marketSegment:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "projectParticipation:write",
 *         "fee:write",
 *         "projectParticipationFee:write",
 *         "nullableMoney:write"
 *     }},
 *     collectionOperations={
 *         "get": {"normalization_context": {"groups": {
 *             "projectParticipation:list",
 *             "project:read",
 *             "projectParticipation:read",
 *             "projectParticipationContact:read",
 *             "projectParticipationFee:read",
 *             "projectParticipationOffer:read",
 *             "projectOrganizer:read",
 *             "projectStatus:read",
 *             "company:read",
 *             "projectStatus:read",
 *             "traceableStatus:read",
 *             "role:read",
 *             "fee:read",
 *             "marketSegment:read",
 *             "nullableMoney:read"
 *         }}},
 *         "post": {
 *             "denormalization_context": {"groups": {
 *                 "projectParticipation:create",
 *                 "projectParticipation:write",
 *                 "projectParticipationContact:write",
 *                 "projectParticipationFee:create",
 *                 "projectParticipationFee:write",
 *                 "fee:write",
 *                 "nullableMoney:write"
 *             }},
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object)"},
 *         "delete": {"security_post_denormalize": "is_granted('edit', previous_object)"},
 *         "put": {"security_post_denormalize": "is_granted('edit', previous_object)"},
 *         "patch": {"security_post_denormalize": "is_granted('edit', previous_object)"}
 *     }
 * )
 *
 * @ApiFilter("Unilend\Filter\CountFilter", properties={"projectParticipationOffers"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter", properties={"project.currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter", properties={"project.currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter", properties={"project.publicId": "exact", "projectParticipationContacts.client.publicId": "exact"})
 * @ApiFilter("Unilend\Filter\InvertedSearchFilter", properties={"project.submitterCompany.publicId"})
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
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;

    public const SERIALIZER_GROUP_ADMIN_READ     = 'projectParticipation:admin:read'; // Additional group that is available for admin (admin user or arranger)
    public const SERIALIZER_GROUP_SENSITIVE_READ = 'projectParticipation:sensitive:read'; // Additional group that is available for public visibility project

    public const BLACKLISTED_COMPANIES = [
        'CA-CIB',
        'Unifergie',
    ];

    private const STATUS_NOT_CONSULTED = 0;
    private const STATUS_CONSULTED     = 10;
    private const STATUS_UNINTERESTED  = 20;

    private const DEFAULT_STATUS = self::STATUS_NOT_CONSULTED;

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
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company", inversedBy="projectParticipations")
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
     * @Groups({"projectParticipation:admin:read"})
     */
    private $projectParticipationContacts;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     *
     * @Assert\Choice(callback="getStatuses")
     * @Assert\NotBlank
     * @Assert\Expression("this.hasOffer() === false or this.isNotInterested() === false")
     */
    private $currentStatus = self::DEFAULT_STATUS;

    /**
     * @var ProjectParticipationFee
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectParticipationFee", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipationFee;

    /**
     * @var ProjectParticipationOffer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationOffer", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"projectParticipation:sensitive:read"})
     */
    private $projectParticipationOffers;

    /**
     * Property created in order that API platform understand it's a nullable field.
     *
     * @var Fee|null
     *
     * @Groups({"projectParticipation:admin:read", "projectParticipation:write"})
     */
    private $fee;

    /**
     * @var NullableMoney
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney", columnPrefix="invitation_")
     *
     * @Groups({"projectParticipation:admin:read", "projectParticipation:write"})
     */
    private $invitationMoney;

    /**
     * @var Collection|ProjectMessage[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectMessage", mappedBy="participation")
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @ApiSubresource
     */
    private $messages;

    /**
     * @param Company            $company
     * @param Project            $project
     * @param Staff              $addedBy
     * @param NullableMoney|null $invitationMoney
     *
     * @throws Exception
     */
    public function __construct(
        Company $company,
        Project $project,
        Staff $addedBy,
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
                return $staff->isActive() && ($staff->isManager() || $staff->isAuditor()) && $staff->getMarketSegments()->contains($project->getMarketSegment());
            })
            ->map(function (Staff $staff) use ($addedBy) {
                return new ProjectParticipationContact($this, $staff->getClient(), $addedBy);
            })
        ;
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
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @return bool
     *
     * @Groups({"projectParticipation:sensitive:read"})
     */
    public function hasOffer(): bool
    {
        return 0 < count($this->projectParticipationOffers);
    }

    /**
     * @return bool
     *
     * @Groups({"projectParticipation:sensitive:read"})
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
     * @Groups({"projectParticipation:admin:read"})
     *
     * @return bool
     */
    public function isNotInterested(): bool
    {
        return $this->currentStatus === static::STATUS_UNINTERESTED;
    }

    /**
     * @return bool
     *
     * @Groups({"projectParticipation:admin:read"})
     */
    public function isConsulted(): bool
    {
        return $this->currentStatus >= static::STATUS_CONSULTED;
    }

    /**
     * @Groups({"projectParticipation:write"})
     *
     * @param bool $uninterested
     *
     * @return ProjectParticipation
     */
    public function setUninterested(bool $uninterested): ProjectParticipation
    {
        $this->currentStatus = $uninterested ? static::STATUS_UNINTERESTED : $this->currentStatus;

        return $this;
    }

    /**
     * @Groups({"projectParticipation:write"})
     *
     * @param bool $consulted The setter needs a parameter to work with API Platform
     *
     * @return ProjectParticipation
     */
    public function setConsulted(bool $consulted): ProjectParticipation
    {
        $this->currentStatus = $consulted && ($this->currentStatus === static::STATUS_NOT_CONSULTED) ?
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
     * @Groups({"projectParticipation:sensitive:read"})
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
     * @Groups({"projectParticipation:admin:read"})
     */
    public function getOfferComment(): ?string
    {
        return $this->projectParticipationOffers->first() ? $this->projectParticipationOffers->first()->getComment() : null;
    }

    /**
     * @return string|null
     *
     * @Groups({"projectParticipation:admin:read"})
     */
    public function getOfferCommitteeStatus(): ?string
    {
        return $this->projectParticipationOffers->first() ? $this->projectParticipationOffers->first()->getCommitteeStatus() : null;
    }

    /**
     * @return DateTimeImmutable|null
     *
     * @Groups({"projectParticipation:admin:read"})
     */
    public function getOfferExpectedCommitteeDate(): ?DateTimeImmutable
    {
        return $this->projectParticipationOffers->first() ? $this->projectParticipationOffers->first()->getExpectedCommitteeDate() : null;
    }

    /**
     * @return Collection
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @param int $currentStatus
     */
    public function setCurrentStatus(int $currentStatus): void
    {
        $this->currentStatus = $currentStatus;
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        return self::getConstants('STATUS_');
    }
}
