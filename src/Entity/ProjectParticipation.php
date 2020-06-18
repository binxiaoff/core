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
use Unilend\Entity\Embeddable\{Offer, OfferWithFee, RangedOfferWithFee};
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "projectParticipation:read",
 *         "projectParticipationContact:read",
 *         "projectParticipationTranche:read",
 *         "projectParticipationStatus:read",
 *         "company:read",
 *         "nullableMoney:read",
 *         "rangedOfferWithFee:read",
 *         "offerWithFee:read",
 *         "offer:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "projectParticipation:write",
 *         "nullableMoney:write",
 *         "rangedOfferWithFee:write",
 *         "offerWithFee:write",
 *         "offer:write"
 *     }},
 *     collectionOperations={
 *         "get": {
 *             "normalization_context": {"groups": {
 *                 "projectParticipation:list",
 *                 "project:read",
 *                 "projectParticipation:read",
 *                 "projectParticipationContact:read",
 *                 "projectParticipationTranche:read",
 *                 "projectParticipationStatus:read",
 *                 "projectOrganizer:read",
 *                 "projectStatus:read",
 *                 "company:read",
 *                 "role:read",
 *                 "marketSegment:read",
 *                 "nullableMoney:read",
 *                 "rangedOfferWithFee:read",
 *                 "offerWithFee:read",
 *                 "offer:read"
 *             }}
 *         },
 *         "post": {
 *             "denormalization_context": {"groups": {
 *                 "projectParticipation:create",
 *                 "projectParticipation:write",
 *                 "nullableMoney:write",
 *                 "rangedOfferWithFee:write",
 *                 "offerWithFee:write",
 *                 "offer:write"
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
 * @UniqueEntity({"project", "participant"})
 */
class ProjectParticipation implements TraceableStatusAwareInterface
{
    use TimestampableTrait;
    use BlamableAddedTrait;
    use PublicizeIdentityTrait;
    use ConstantsAwareTrait;

    // Additional normalizer group that is available for those who have the admin right on the participation (participation owner or arranger)
    public const SERIALIZER_GROUP_ADMIN_READ = 'projectParticipation:admin:read';
    // Additional normalizer group that is available for public visibility project. It's also available for the participation owner and arranger
    public const SERIALIZER_GROUP_SENSITIVE_READ = 'projectParticipation:sensitive:read';

    // Additional denormalizer group that is available for the participation owner in all steps
    public const SERIALIZER_GROUP_PARTICIPATION_OWNER_WRITE = 'projectParticipation:participationOwner:write';

    // Additional denormalizer group that is available for the participation owner in interest collection step
    public const SERIALIZER_GROUP_PARTICIPATION_OWNER_INTEREST_COLLECTION_WRITE = 'projectParticipation:participationOwner:interestCollection:write';
    // Additional denormalizer group that is available for the arranger in interest collection step
    public const SERIALIZER_GROUP_ARRANGER_INTEREST_COLLECTION_WRITE = 'projectParticipation:arranger:interestCollection:write';

    // Additional denormalizer group that is available for the participation owner in offer negotiation step
    public const SERIALIZER_GROUP_PARTICIPATION_OWNER_OFFER_NEGOTIATION_WRITE = 'projectParticipation:participationOwner:offerNegotiation:write';
    // Additional denormalizer group that is available for the arranger in offer negotiation step
    public const SERIALIZER_GROUP_ARRANGER_OFFER_NEGOTIATION_WRITE = 'projectParticipation:arranger:offerNegotiation:write';

    // Additional denormalizer group that is available for the participation owner in contract negotiation step
    public const SERIALIZER_GROUP_PARTICIPANT_CONTRACT_NEGOTIATION_OWNER_WRITE = 'projectParticipation:participationOwner:contractNegotiation:write';
    // Additional denormalizer group that is available for the arranger in interest collection step
    public const SERIALIZER_GROUP_ARRANGER_CONTRACT_NEGOTIATION_WRITE = 'projectParticipation:arranger:contractNegotiation:write';

    public const BLACKLISTED_COMPANIES = [
        'CA-CIB',
        'Unifergie',
    ];

    public const COMMITTEE_STATUS_PENDED   = 'pended';
    public const COMMITTEE_STATUS_ACCEPTED = 'accepted';
    public const COMMITTEE_STATUS_REJECTED = 'rejected';

    public const FIELD_COMMITTEE_STATUS = 'committeeStatus';

    private const INVITATION_REPLY_MODE_PRO_RATA   = 'pro-rata';
    private const INVITATION_REPLY_MODE_CUSTOMIZED = 'customized';

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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"projectParticipation:read", "projectParticipation:create"})
     *
     * @Assert\Expression(
     *     "this.isParticipantValid()",
     *     message="ProjectParticipation.participant.notValid"
     * )
     *
     * @Assert\NotBlank
     */
    private $participant;

    /**
     * @var ProjectParticipationStatus|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectParticipationStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
     */
    private $currentStatus;

    /**
     * Participant committee status.
     *
     * @var string
     *
     * @ORM\Column(length=30, nullable=true)
     *
     * @Assert\Expression(
     *     "this.isCommitteeStatusValid()",
     *     message="ProjectParticipation.committeeStatus.pendedDeadline"
     * )
     * @Assert\Choice(callback="getPossibleCommitteeStatus")
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_OFFER_NEGOTIATION_WRITE})
     */
    private $committeeStatus;

    /**
     * Participant committee response deadline if the status = "pended".
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_OFFER_NEGOTIATION_WRITE})
     */
    private $committeeDeadline;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_OFFER_NEGOTIATION_WRITE})
     */
    private $committeeComment;

    /**
     * @var RangedOfferWithFee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\RangedOfferWithFee")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ, ProjectParticipation::SERIALIZER_GROUP_ARRANGER_INTEREST_COLLECTION_WRITE, "projectParticipation:create"})
     */
    private $interestRequest;

    /**
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_INTEREST_COLLECTION_WRITE})
     */
    private $interestReply;

    /**
     * @var OfferWithFee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\OfferWithFee")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ, ProjectParticipation::SERIALIZER_GROUP_ARRANGER_OFFER_NEGOTIATION_WRITE, "projectParticipation:create"})
     */
    private $invitationRequest;

    /**
     * @var string|null
     *
     * @ORM\Column(length=10, nullable=true)
     *
     * @Assert\Choice(callback="getPossibleInvitationReplyMode")
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_OFFER_NEGOTIATION_WRITE})
     */
    private $invitationReplyMode;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\NotBlank(allowNull=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, ProjectParticipation::SERIALIZER_GROUP_ARRANGER_CONTRACT_NEGOTIATION_WRITE})
     */
    private $allocationFeeRate;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ, ProjectParticipation::SERIALIZER_GROUP_PARTICIPATION_OWNER_WRITE})
     */
    private $participantLastConsulted;

    /**
     * @var ProjectParticipationContact[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationContact", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
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
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationTranche", mappedBy="projectParticipation")
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ})
     */
    private $projectParticipationTranches;

    /**
     * @var ArrayCollection|ProjectParticipationStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationStatus", mappedBy="projectParticipation", cascade={"persist"})
     * @ORM\OrderBy({"added": "ASC"})
     */
    private $statuses;

    /**
     * @param Company $participant
     * @param Project $project
     * @param Staff   $addedBy
     *
     * @throws Exception
     */
    public function __construct(Company $participant, Project $project, Staff $addedBy)
    {
        $this->added                        = new DateTimeImmutable();
        $this->addedBy                      = $addedBy;
        $this->participant                  = $participant;
        $this->project                      = $project;
        $this->projectParticipationContacts = new ArrayCollection();
        $this->messages                     = new ArrayCollection();
        $this->statuses                     = new ArrayCollection();
        $this->interestRequest              = new RangedOfferWithFee();
        $this->interestReply                = new Offer();
        $this->invitationRequest            = new OfferWithFee();

        $this->setCurrentStatus(new ProjectParticipationStatus($this, ProjectParticipationStatus::STATUS_ACTIVE, $addedBy));

        $this->projectParticipationContacts = $participant->getStaff()
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
    public function getParticipant(): Company
    {
        return $this->participant;
    }

    /**
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
     *
     * @return ProjectParticipationStatus|null
     */
    public function getCurrentStatus(): ?ProjectParticipationStatus
    {
        return $this->currentStatus;
    }

    /**
     * @param ProjectParticipationStatus|Statusinterface $currentStatus
     *
     * @return ProjectParticipation
     */
    public function setCurrentStatus(Statusinterface $currentStatus): ProjectParticipation
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
     */
    public function getParticipantLastConsulted(): ?DateTimeImmutable
    {
        return $this->participantLastConsulted;
    }

    /**
     * @param DateTimeImmutable|null $participantLastConsulted
     *
     * @return ProjectParticipation
     */
    public function setParticipantLastConsulted(?DateTimeImmutable $participantLastConsulted): ProjectParticipation
    {
        $this->participantLastConsulted = $participantLastConsulted;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCommitteeStatus(): ?string
    {
        return $this->committeeStatus;
    }

    /**
     * @param string|null $committeeStatus
     *
     * @return ProjectParticipation
     */
    public function setCommitteeStatus(?string $committeeStatus): ProjectParticipation
    {
        $this->committeeStatus = $committeeStatus;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getCommitteeDeadline(): ?DateTimeImmutable
    {
        return $this->committeeDeadline;
    }

    /**
     * @param DateTimeImmutable|null $committeeDeadline
     *
     * @return ProjectParticipation
     */
    public function setCommitteeDeadline(?DateTimeImmutable $committeeDeadline): ProjectParticipation
    {
        $this->committeeDeadline = $committeeDeadline;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCommitteeComment(): ?string
    {
        return $this->committeeComment;
    }

    /**
     * @param string|null $committeeComment
     *
     * @return ProjectParticipation
     */
    public function setCommitteeComment(?string $committeeComment): ProjectParticipation
    {
        $this->committeeComment = $committeeComment;

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
     * @return string|null
     */
    public function getInvitationReplyMode(): ?string
    {
        return $this->invitationReplyMode;
    }

    /**
     * @param string|null $invitationReplyMode
     *
     * @return ProjectParticipation
     */
    public function setInvitationReplyMode(?string $invitationReplyMode): ProjectParticipation
    {
        $this->invitationReplyMode = $invitationReplyMode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAllocationFeeRate(): ?string
    {
        return $this->allocationFeeRate;
    }

    /**
     * @param string|null $allocationFeeRate
     *
     * @return ProjectParticipation
     */
    public function setAllocationFeeRate(?string $allocationFeeRate): ProjectParticipation
    {
        $this->allocationFeeRate = $allocationFeeRate;

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

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return ProjectParticipationStatus::STATUS_ACTIVE === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @return ArrayCollection|ProjectParticipationTranche[]
     */
    public function getProjectParticipationTranches()
    {
        return $this->projectParticipationTranches;
    }

    /**
     * @return array
     */
    public static function getPossibleCommitteeStatus(): array
    {
        return static::getConstants('COMMITTEE_STATUS_');
    }

    /**
     * @return array
     */
    public static function getPossibleInvitationReplyMode(): array
    {
        return static::getConstants('INVITATION_REPLY_MODE_');
    }

    /**
     * Used in an expression constraints: A pended committee response need a deadline.
     *
     * @return bool
     */
    public function isCommitteeStatusValid(): bool
    {
        if (self::COMMITTEE_STATUS_PENDED === $this->getCommitteeStatus()) {
            return null !== $this->getCommitteeDeadline();
        }

        return true;
    }

    /**
     * Used in an expression constraints: if the participant is not blacklisted.
     *
     * @return bool
     */
    public function isParticipantValid(): bool
    {
        $blacklist = array_map('strtolower', ProjectParticipation::BLACKLISTED_COMPANIES);

        return false === \in_array(mb_strtolower($this->getParticipant()->getName()), $blacklist, true);
    }
}
