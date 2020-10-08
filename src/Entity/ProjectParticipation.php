<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource, ApiSubresource};
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\{Constraints as Assert, Context\ExecutionContextInterface};
use Unilend\Entity\Embeddable\{NullableMoney, Offer, OfferWithFee, RangedOfferWithFee};
use Unilend\Entity\Interfaces\{MoneyInterface, StatusInterface, TraceableStatusAwareInterface};
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Service\MoneyCalculator;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "projectParticipation:read",
 *         "projectParticipationMember:read",
 *         "projectParticipationTranche:read",
 *         "projectParticipationStatus:read",
 *         "projectStatus:read",
 *         "project:read",
 *         "company:read",
 *         "nullableMoney:read",
 *         "money:read",
 *         "rangedOfferWithFee:read",
 *         "offerWithFee:read",
 *         "offer:read",
 *         "archivable:read",
 *         "timestampable:read",
 *         "companyStatus:read",
 *         "role:read",
 *         "file:read",
 *         "fileVersion:read",
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
 *                 "projectParticipationMember:read",
 *                 "projectParticipationTranche:read",
 *                 "projectParticipationStatus:read",
 *                 "projectOrganizer:read",
 *                 "projectStatus:read",
 *                 "company:read",
 *                 "role:read",
 *                 "nullableMoney:read",
 *                 "money:read",
 *                 "rangedOfferWithFee:read",
 *                 "offerWithFee:read",
 *                 "offer:read",
 *                 "archivable:read",
 *                 "timestampable:read",
 *                 "companyStatus:read"
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
 *         "get": {
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {
 *                 "groups": {
 *                     "projectParticipation:read",
 *                     "projectParticipationMember:read",
 *                     "projectParticipationTranche:read",
 *                     "projectParticipationStatus:read",
 *                     "projectStatus:read",
 *                     "project:read",
 *                     "role:read",
 *                     "company:read",
 *                     "nullableMoney:read",
 *                     "money:read",
 *                     "rangedOfferWithFee:read",
 *                     "offerWithFee:read",
 *                     "offer:read",
 *                     "archivable:read",
 *                     "timestampable:read",
 *                     "file:read",
 *                     "fileVersion:read",
 *                     "tranche:read",
 *                     "lendingRate:read",
 *                     "companyStatus:read",
 *                     "role:read"
 *                 }
 *             }
 *         },
 *         "delete": {"security": "is_granted('delete', object)"},
 *         "put": {"security": "is_granted('edit', object)"},
 *         "patch": {
 *            "security": "is_granted('edit', object)",
 *            "denormalization_context": {"groups": {
 *                 "projectParticipationTranche:write",
 *                 "projectParticipationMember:write",
 *                 "projectParticipationStatus:create",
 *                 "projectParticipation:write",
 *                 "nullableMoney:write",
 *                 "rangedOfferWithFee:write",
 *                 "offerWithFee:write",
 *                 "offer:write"
 *             }},
 *         }
 *     }
 * )
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectParticipation")
 *
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter", properties={"project.currentStatus.status", "currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter", properties={"project.currentStatus.status", "currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter", properties={
 *     "project.publicId": "exact",
 *     "projectParticipationMembers.staff.publicId": "exact",
 *     "participant.publicId": "exact"
 * })
 * @ApiFilter("Unilend\Filter\InvertedSearchFilter", properties={"project.submitterCompany.publicId"})
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"participant", "project"})
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

    public const PROJECT_PARTICIPATION_FILE_TYPE_NDA = 'project_participation_nda';

    public const INVITATION_REPLY_MODE_PRO_RATA   = 'pro-rata';
    public const INVITATION_REPLY_MODE_CUSTOMIZED = 'customized';

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
    private Project $project;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company")
     * @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     *
     * @Groups({"projectParticipation:read", "projectParticipation:create"})
     *
     * @Assert\NotBlank
     */
    private Company $participant;

    /**
     * @var ProjectParticipationStatus|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectParticipationStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({
     *     ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ,
     *     "projectParticipation:owner:interestRequest:write",
     *     "projectParticipation:owner:participantReply:write",
     *     "projectParticipation:arranger:write"
     * })
     */
    private ?ProjectParticipationStatus $currentStatus;

    /**
     * Participant committee response deadline if the status = "pended".
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({
     *     ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ,
     *     "projectParticipation:owner:participantReply:write"
     * })
     */
    private ?DateTimeImmutable $committeeDeadline = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({
     *     ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ,
     *     "projectParticipation:owner:write"
     * })
     */
    private ?string $committeeComment = null;

    /**
     * Marque d'interet sollicitation envoyé par l'arrangeur au participant.
     *
     * @var RangedOfferWithFee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\RangedOfferWithFee")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({
     *     ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ,
     *     "projectParticipation:arranger:interestExpression:write",
     *     "projectParticipation:create"
     * })
     */
    private RangedOfferWithFee $interestRequest;

    /**
     * Réponse de la sollicitation de l'arrangeur envoyé au participant.
     *
     * @var Offer
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, "projectParticipation:owner:interestExpression:write"})
     */
    private Offer $interestReply;

    /**
     * Réponse ferme : Invitation envoyé par l'arrangeur au participant.
     *
     * @var OfferWithFee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\OfferWithFee")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ, "projectParticipation:arranger:participantReply:write", "projectParticipation:create"})
     */
    private OfferWithFee $invitationRequest;

    /**
     * @var string|null
     *
     * @ORM\Column(length=10, nullable=true)
     *
     * @Assert\Choice(callback="getPossibleInvitationReplyMode")
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, "projectParticipation:owner:participantReply:write"})
     */
    private ?string $invitationReplyMode = null;

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
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, "projectParticipation:arranger:participantReply:write"})
     */
    private ?string $allocationFeeRate = null;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
     */
    private ?DateTimeImmutable $participantLastConsulted = null;

    /**
     * @var Collection|ProjectParticipationMember[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationMember", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Assert\Valid
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
     */
    private Collection $projectParticipationMembers;

    /**
     * @var Collection|ProjectMessage[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectMessage", mappedBy="participation")
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @ApiSubresource
     */
    private Collection $messages;

    /**
     * @var Collection|ProjectParticipationTranche[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationTranche", mappedBy="projectParticipation", cascade={"persist"})
     *
     * @Assert\Valid
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ})
     */
    private Collection $projectParticipationTranches;

    /**
     * @var Collection|ProjectParticipationStatus[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationStatus", mappedBy="projectParticipation", cascade={"persist"})
     * @ORM\OrderBy({"added": "ASC"})
     */
    private Collection $statuses;

    /**
     * @var File|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File")
     * @ORM\JoinColumn(name="id_nda")
     *
     * @Groups({
     *     ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ,
     *     "projectParticipation:arranger:interestExpression:write",
     *     "projectParticipation:arranger:participantReply:write",
     *     "projectParticipation:arranger:draft:write"
     * })
     */
    private ?File $nda = null;

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
        $this->projectParticipationMembers  = new ArrayCollection();
        $this->messages                     = new ArrayCollection();
        $this->statuses                     = new ArrayCollection();
        $this->projectParticipationTranches = new ArrayCollection();
        $this->interestRequest              = new RangedOfferWithFee();
        $this->interestReply                = new Offer();
        $this->invitationRequest            = new OfferWithFee();

        $this->setCurrentStatus(new ProjectParticipationStatus($this, ProjectParticipationStatus::STATUS_CREATED, $addedBy));
    }

    /**
     * @return array
     */
    public static function getFileTypes()
    {
        return [static::PROJECT_PARTICIPATION_FILE_TYPE_NDA];
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
     * @return ProjectParticipationStatus|null
     */
    public function getCurrentStatus(): ?ProjectParticipationStatus
    {
        return $this->currentStatus;
    }

    /**
     * @param ProjectParticipationStatus|StatusInterface $currentStatus
     *
     * @return ProjectParticipation
     */
    public function setCurrentStatus(StatusInterface $currentStatus): ProjectParticipation
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
     * @return ProjectParticipationMember[]|Collection
     */
    public function getProjectParticipationMembers(): Collection
    {
        return $this->projectParticipationMembers;
    }

    /**
     * @return ProjectParticipationMember[]|Collection
     */
    public function getActiveProjectParticipationMembers(): Collection
    {
        return $this->projectParticipationMembers->filter(static function (ProjectParticipationMember $projectParticipationMember) {
            return false === $projectParticipationMember->isArchived();
        });
    }

    /**
     * @param ProjectParticipationMember $projectParticipationMember
     *
     * @return ProjectParticipation
     */
    public function addProjectParticipationMember(ProjectParticipationMember $projectParticipationMember): ProjectParticipation
    {
        if (false === $this->projectParticipationMembers->contains($projectParticipationMember)) {
            $this->projectParticipationMembers->add($projectParticipationMember);
        }

        return $this;
    }

    /**
     * @return Collection|ProjectMessage[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @return Collection|ProjectParticipationStatus[]
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
        return $this->getCurrentStatus() && 0 < $this->getCurrentStatus()->getStatus();
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->getCurrentStatus()
            && \in_array(
                $this->getCurrentStatus()->getStatus(),
                [ProjectParticipationStatus::STATUS_ARCHIVED_BY_ARRANGER, ProjectParticipationStatus::STATUS_ARCHIVED_BY_PARTICIPANT],
                true
            );
    }
    /**
     * @return ArrayCollection|ProjectParticipationTranche[]
     */
    public function getProjectParticipationTranches()
    {
        return $this->projectParticipationTranches;
    }

    /**
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ})
     *
     * @return MoneyInterface
     */
    public function getTotalInvitationReply(): MoneyInterface
    {
        $totalInvitationReply = new NullableMoney();
        foreach ($this->projectParticipationTranches as $projectParticipationTranche) {
            $totalInvitationReply = MoneyCalculator::add($totalInvitationReply, $projectParticipationTranche->getInvitationReply()->getMoney());
        }

        return $totalInvitationReply;
    }

    /**
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ})
     *
     * @return MoneyInterface
     */
    public function getTotalAllocation(): MoneyInterface
    {
        $totalAllocation = new NullableMoney();
        foreach ($this->projectParticipationTranches as $projectParticipationTranche) {
            $totalAllocation = MoneyCalculator::add($totalAllocation, $projectParticipationTranche->getAllocation()->getMoney());
        }

        return $totalAllocation;
    }

    /**
     * @return array
     */
    public static function getPossibleInvitationReplyMode(): array
    {
        return static::getConstants('INVITATION_REPLY_MODE_');
    }

    /**
     * @return File|null
     */
    public function getNda(): ?File
    {
        return $this->nda;
    }

    /**
     * @param File|null $nda
     *
     * @return ProjectParticipation
     */
    public function setNda(?File $nda): ProjectParticipation
    {
        $this->nda = $nda;

        return $this;
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     *
     * @return ProjectParticipation
     */
    public function addProjectParticipationTranche(ProjectParticipationTranche $projectParticipationTranche): ProjectParticipation
    {
        if (false === $this->hasProjectParticipationTranche($projectParticipationTranche)) {
            $this->projectParticipationTranches->add($projectParticipationTranche);
        }

        $tranche = $projectParticipationTranche->getTranche();

        if (false === $tranche->hasProjectParticipationTranche($projectParticipationTranche)) {
            $tranche->addProjectParticipationTranche($projectParticipationTranche);
        }

        return $this;
    }

    /**
     * @param ProjectParticipationTranche $projectParticipationTranche
     *
     * @return bool
     */
    public function hasProjectParticipationTranche(ProjectParticipationTranche $projectParticipationTranche): bool
    {
        return ($this->projectParticipationTranches->contains($projectParticipationTranche));
    }

    /**
     * @return bool
     */
    public function isArrangerParticipation(): bool
    {
        return $this->getParticipant() === $this->getProject()->getArranger();
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function validateSendingInvitation(ExecutionContextInterface $context): void
    {
        if ($this->getProject()->hasCompletedStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION)) {
            if ((null === $this->getInvitationRequest() || false === $this->invitationRequest->isValid())) {
                $context->buildViolation('ProjectParticipation.invitationRequest.invalid')
                    ->atPath('invitationRequest')
                    ->addViolation();
            }

            if ($this->projectParticipationTranches->isEmpty() && $this->getCurrentStatus()->getStatus() > 0) {
                $context->buildViolation('ProjectParticipation.projectParticipationTranches.required')
                    ->atPath('projectParticipationTranches')
                    ->addViolation();
            }
        }
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function validateProjectParticipationTranches(ExecutionContextInterface $context): void
    {
        foreach ($this->projectParticipationTranches as $index => $participationTranche) {
            if ($participationTranche->getProjectParticipation() !== $this) {
                $context->buildViolation('ProjectParticipation.projectParticipationTranches.incorrectParticipation')
                    ->atPath("projectParticipationTranches[$index]")
                    ->addViolation();
            }
        }
    }

    /**
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function validateProjectParticipationMembers(ExecutionContextInterface $context): void
    {
        foreach ($this->projectParticipationMembers as $index => $participationMember) {
            if ($participationMember->getProjectParticipation() !== $this) {
                $context->buildViolation('ProjectParticipation.projectParticipationMembers.incorrectParticipation')
                    ->atPath("projectParticipationMembers[$index]")
                    ->addViolation();
            }
        }
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateCommitteeDeadline(ExecutionContextInterface $context): void
    {
        if (null === $this->committeeDeadline && ProjectParticipationStatus::STATUS_COMMITTEE_PENDED === $this->currentStatus->getStatus()) {
            $context->buildViolation('ProjectParticipation.committeeDeadline.required')
                ->atPath('committeeDeadline')
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateCurrencyConsistency(ExecutionContextInterface $context): void
    {
        $globalFundingMoney = $this->getProject()->getGlobalFundingMoney();

        if (MoneyCalculator::isDifferentCurrency($this->interestRequest->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Money.currency.inconsistent')
                ->atPath('interestRequest.money')
                ->addViolation();
        }

        if (MoneyCalculator::isDifferentCurrency($this->interestRequest->getMinMoney(), $globalFundingMoney)) {
            $context->buildViolation('Money.currency.inconsistent')
                ->atPath('interestRequest.minMoney')
                ->addViolation();
        }

        if (MoneyCalculator::isDifferentCurrency($this->interestReply->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Money.currency.inconsistent')
                ->atPath('interestReply')
                ->addViolation();
        }

        if (MoneyCalculator::isDifferentCurrency($this->invitationRequest->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Money.currency.inconsistent')
                ->atPath('invitationRequest')
                ->addViolation();
        }
    }
}
