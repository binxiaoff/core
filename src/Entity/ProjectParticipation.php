<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource, ApiSubresource};
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\{Fee, Offer, OfferWithFee, RangedOfferWithFee};
use Unilend\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "projectParticipation:read",
 *         "projectParticipationContact:read",
 *         "projectOrganizer:read",
 *         "company:read",
 *         "fee:read",
 *         "nullableMoney:read",
 *         "ProjectParticipationTranche:read",
 *         "marketSegment:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "projectParticipation:write",
 *         "fee:write",
 *         "nullableMoney:write"
 *     }},
 *     collectionOperations={
 *         "get": {"normalization_context": {"groups": {
 *             "projectParticipation:list",
 *             "project:read",
 *             "projectParticipation:read",
 *             "projectParticipationContact:read",
 *             "projectParticipationTranche:read",
 *             "projectOrganizer:read",
 *             "projectStatus:read",
 *             "company:read",
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
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectParticipation")
 *
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter", properties={"project.currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter", properties={"project.currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter", properties={"project.publicId": "exact", "projectParticipationContacts.client.publicId": "exact"})
 * @ApiFilter("Unilend\Filter\InvertedSearchFilter", properties={"project.submitterCompany.publicId"})
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"project", "company"})
 */
class ProjectParticipation implements TraceableStatusAwareInterface
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

    public const COMMITTEE_STATUS_PENDED   = 'pended';
    public const COMMITTEE_STATUS_ACCEPTED = 'accepted';
    public const COMMITTEE_STATUS_REJECTED = 'rejected';

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectParticipations")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
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
     * @var ProjectParticipationStatus
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectParticipationStatus")
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"projectParticipation:admin:read", "projectParticipation:update"})
     */
    private $currentStatus;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipation:sensitive:read", "projectParticipation:participantOwner:write"})
     */
    private $committeeStatus;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipation:sensitive:read", "projectParticipation:participantOwner:write"})
     */
    private $committeeDeadline;

    /**
     * @var RangedOfferWithFee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\RangedOfferWithFee")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipation:admin:read", "projectParticipation:arrangerOwner:write"})
     */
    private $interestRequest;

    /**
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipation:sensitive:read", "projectParticipation:participantOwner:write"})
     */
    private $interestReply;

    /**
     * @var OfferWithFee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\OfferWithFee")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipation:admin:read", "projectParticipation:arrangerOwner:write"})
     */
    private $invitationRequest;

    /**
     * @var Fee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Fee")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipation:sensitive:read", "projectParticipation:arrangerOwner:write"})
     */
    private $allocationFee;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"projectParticipation:admin:read", "projectParticipation:participantOwner:write"})
     */
    private $participantLastConsulted;

    /**
     * @var ProjectParticipationContact[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationContact", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"projectParticipation:admin:read"})
     */
    private $projectParticipationContacts;

    /**
     * @var ArrayCollection|ProjectMessage[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectMessage", mappedBy="participation")
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @ApiSubresource
     */
    private $messages;

    /**
     * @var ArrayCollection|ProjectParticipationTranche[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationTranche", mappedBy="participation")
     */
    private $projectParticipationTranches;

    /**
     * @var ArrayCollection|ProjectParticipationStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationStatus", mappedBy="participation", cascade={"persist"})
     * @ORM\OrderBy({"added": "ASC"})
     */
    private $statuses;

    /**
     * @param Company $company
     * @param Project $project
     * @param Staff   $addedBy
     *
     * @throws Exception
     */
    public function __construct(Company $company, Project $project, Staff $addedBy)
    {
        $this->added                        = new DateTimeImmutable();
        $this->addedBy                      = $addedBy;
        $this->company                      = $company;
        $this->project                      = $project;
        $this->committeeStatus              = self::COMMITTEE_STATUS_PENDED;
        $this->projectParticipationContacts = new ArrayCollection();
        $this->messages                     = new ArrayCollection();
        $this->statuses                     = new ArrayCollection();
        $this->setCurrentStatus(new ProjectParticipationStatus($this, ProjectParticipationStatus::STATUS_ACTIVE, $addedBy));

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
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @Groups({"projectParticipation:admin:read"})
     *
     * @return ProjectParticipationStatus
     */
    public function getCurrentStatus(): ProjectParticipationStatus
    {
        return $this->currentStatus;
    }

    /**
     * @param ProjectParticipationStatus $currentStatus
     *
     * @return ProjectParticipation
     */
    public function setCurrentStatus(ProjectParticipationStatus $currentStatus): ProjectParticipation
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     *
     * @Groups({"projectParticipation:admin:read"})
     */
    public function getParticipantLastConsulted(): DateTimeImmutable
    {
        return $this->participantLastConsulted;
    }

    /**
     * @param DateTimeImmutable $participantLastConsulted
     *
     * @return ProjectParticipation
     */
    public function setParticipantLastConsulted(DateTimeImmutable $participantLastConsulted): ProjectParticipation
    {
        $this->participantLastConsulted = $participantLastConsulted;

        return $this;
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
     * @return ProjectParticipation
     */
    public function setCommitteeStatus(string $committeeStatus): ProjectParticipation
    {
        $this->committeeStatus = $committeeStatus;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCommitteeDeadline(): DateTimeImmutable
    {
        return $this->committeeDeadline;
    }

    /**
     * @param DateTimeImmutable $committeeDeadline
     *
     * @return ProjectParticipation
     */
    public function setCommitteeDeadline(DateTimeImmutable $committeeDeadline): ProjectParticipation
    {
        $this->committeeDeadline = $committeeDeadline;

        return $this;
    }

    /**
     * @return RangedOfferWithFee
     */
    public function getInterestRequest(): RangedOfferWithFee
    {
        return $this->interestRequest;
    }

    /**
     * @param RangedOfferWithFee $interestRequest
     *
     * @return ProjectParticipation
     */
    public function setInterestRequest(RangedOfferWithFee $interestRequest): ProjectParticipation
    {
        $this->interestRequest = $interestRequest;

        return $this;
    }

    /**
     * @return Offer
     */
    public function getInterestReply(): Offer
    {
        return $this->interestReply;
    }

    /**
     * @param Offer $interestReply
     *
     * @return ProjectParticipation
     */
    public function setInterestReply(Offer $interestReply): ProjectParticipation
    {
        $this->interestReply = $interestReply;

        return $this;
    }

    /**
     * @return OfferWithFee
     */
    public function getInvitationRequest(): OfferWithFee
    {
        return $this->invitationRequest;
    }

    /**
     * @param OfferWithFee $invitationRequest
     *
     * @return ProjectParticipation
     */
    public function setInvitationRequest(OfferWithFee $invitationRequest): ProjectParticipation
    {
        $this->invitationRequest = $invitationRequest;

        return $this;
    }

    /**
     * @return Fee
     */
    public function getAllocationFee(): Fee
    {
        return $this->allocationFee;
    }

    /**
     * @param Fee $allocationFee
     *
     * @return ProjectParticipation
     */
    public function setAllocationFee(Fee $allocationFee): ProjectParticipation
    {
        $this->allocationFee = $allocationFee;

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
     * @return Collection
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @return Collection|ProjectParticipationStatus
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }
}
