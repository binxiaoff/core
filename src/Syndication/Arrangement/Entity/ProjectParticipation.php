<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\File;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\Interfaces\FileTypesAwareInterface;
use KLS\Core\Entity\Interfaces\MoneyInterface;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Service\MoneyCalculator;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\Syndication\Arrangement\Entity\Embeddable\Offer;
use KLS\Syndication\Arrangement\Entity\Embeddable\OfferWithFee;
use KLS\Syndication\Arrangement\Entity\Embeddable\RangedOfferWithFee;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "projectParticipation:read",
 *             "projectParticipationMember:read",
 *             "projectParticipationTranche:read",
 *             "projectParticipationStatus:read",
 *             "projectStatus:read",
 *             "project:read",
 *             "company:read",
 *             "nullableMoney:read",
 *             "money:read",
 *             "rangedOfferWithFee:read",
 *             "offerWithFee:read",
 *             "offer:read",
 *             "archivable:read",
 *             "timestampable:read",
 *             "companyStatus:read",
 *             "file:read",
 *             "fileVersion:read",
 *             "invitationReplyVersion:read",
 *             "interestReplyVersion:read",
 *             "tranche:read",
 *             "companyGroupTag:read",
 *             "permission:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "projectParticipation:write",
 *             "nullableMoney:write",
 *             "rangedOfferWithFee:write",
 *             "offerWithFee:write",
 *             "offer:write",
 *             "permission:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     collectionOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "projectParticipation:list",
 *                     "project:read",
 *                     "projectParticipation:read",
 *                     "projectParticipationMember:read",
 *                     "projectParticipationTranche:read",
 *                     "projectParticipationStatus:read",
 *                     "projectOrganizer:read",
 *                     "projectStatus:read",
 *                     "company:read",
 *                     "nullableMoney:read",
 *                     "money:read",
 *                     "rangedOfferWithFee:read",
 *                     "offerWithFee:read",
 *                     "offer:read",
 *                     "archivable:read",
 *                     "timestampable:read",
 *                     "companyStatus:read",
 *                     "companyGroupTag:read",
 *                     "permission:read",
 *                 },
 *                 "openapi_definition_name": "collection-get-read",
 *             },
 *         },
 *         "post": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "projectParticipation:create",
 *                     "projectParticipation:write",
 *                     "nullableMoney:write",
 *                     "rangedOfferWithFee:write",
 *                     "offerWithFee:write",
 *                     "offer:write",
 *                     "permission:write",
 *                 },
 *                 "openapi_definition_name": "collection-post-write",
 *             },
 *             "security_post_denormalize": "is_granted('create', object)",
 *         },
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
 *                     "invitationReplyVersion:read",
 *                     "interestReplyVersion:read",
 *                     "companyGroupTag:read",
 *                     "permission:read",
 *                 },
 *                 "openapi_definition_name": "item-get-read",
 *             },
 *         },
 *         "delete": {"security": "is_granted('delete', object)"},
 *         "put": {"security": "is_granted('edit', object)"},
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "projectParticipationTranche:write",
 *                     "projectParticipationMember:write",
 *                     "projectParticipationStatus:create",
 *                     "projectParticipation:write",
 *                     "nullableMoney:write",
 *                     "rangedOfferWithFee:write",
 *                     "offerWithFee:write",
 *                     "offer:write",
 *                     "permission:write",
 *                 },
 *                 "openapi_definition_name": "item-patch-write",
 *             },
 *         },
 *     },
 * )
 *
 * @Gedmo\Loggable(logEntryClass="KLS\Syndication\Arrangement\Entity\Versioned\VersionedProjectParticipation")
 *
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter", properties={"project.currentStatus.status", "currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter", properties={"project.currentStatus.status", "currentStatus.status"})
 * @ApiFilter("ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter", properties={
 *     "project.publicId": "exact",
 *     "projectParticipationMembers.staff.publicId": "exact",
 *     "participant.publicId": "exact"
 * })
 * @ApiFilter("KLS\Core\Filter\InvertedSearchFilter", properties={"project.submitterCompany.publicId"})
 *
 * @ORM\Table(
 *     name="syndication_project_participation",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})}
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"participant", "project"})
 */
class ProjectParticipation implements TraceableStatusAwareInterface, FileTypesAwareInterface
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
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Arrangement\Entity\Project", inversedBy="projectParticipations")
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
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=false)
     *
     * @Groups({"projectParticipation:read", "projectParticipation:create"})
     *
     * @Assert\NotBlank
     */
    private Company $participant;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({
     *     ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ,
     *     "projectParticipation:owner:interestExpression:write",
     *     "projectParticipation:owner:participantReply:write",
     *     "projectParticipation:arranger:interestExpression:write",
     *     "projectParticipation:arranger:participantReply:write",
     *     "projectParticipation:arrangerOwner:allocation:write"
     * })
     */
    private ?ProjectParticipationStatus $currentStatus;

    /**
     * Participant committee response deadline if the status = "pended".
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({
     *     ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ,
     *     "projectParticipation:owner:participantReply:write",
     *     "projectParticipation:arrangerOwner:allocation:write"
     * })
     */
    private ?DateTimeImmutable $committeeDeadline = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({
     *     ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ,
     *     "projectParticipation:owner:participantReply:write",
     *     "projectParticipation:arrangerOwner:allocation:write"
     * })
     */
    private ?string $committeeComment = null;

    /**
     * Marque d'interet sollicitation envoyé par l'arrangeur au participant.
     *
     * @ORM\Embedded(class="KLS\Syndication\Arrangement\Entity\Embeddable\RangedOfferWithFee")
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
     * @ORM\Embedded(class="KLS\Syndication\Arrangement\Entity\Embeddable\Offer")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ, "projectParticipation:owner:interestExpression:write"})
     */
    private Offer $interestReply;

    /**
     * @var Collection|InterestReplyVersion[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Arrangement\Entity\InterestReplyVersion", mappedBy="projectParticipation", orphanRemoval=true)
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ})
     */
    private Collection $interestReplyVersions;

    /**
     * Réponse ferme : Invitation envoyé par l'arrangeur au participant.
     *
     * @ORM\Embedded(class="KLS\Syndication\Arrangement\Entity\Embeddable\OfferWithFee")
     *
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ, "projectParticipation:arranger:participantReply:write", "projectParticipation:create"})
     */
    private OfferWithFee $invitationRequest;

    /**
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
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
     */
    private ?DateTimeImmutable $participantLastConsulted = null;

    /**
     * @var Collection|ProjectParticipationMember[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectParticipationMember", mappedBy="projectParticipation", cascade={"persist"}, orphanRemoval=true)
     *
     * @Assert\Valid
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
     */
    private Collection $projectParticipationMembers;

    /**
     * @var Collection|ProjectParticipationTranche[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectParticipationTranche", mappedBy="projectParticipation", cascade={"persist"})
     *
     * @Assert\Valid
     *
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_SENSITIVE_READ})
     */
    private Collection $projectParticipationTranches;

    /**
     * @var Collection|ProjectParticipationStatus[]
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectParticipationStatus", mappedBy="projectParticipation", cascade={"persist"})
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"projectParticipation:read"})
     */
    private Collection $statuses;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\File")
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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Arrangement\Entity\NDASignature", mappedBy="projectParticipation")
     */
    private iterable $ndaSignatures;

    /**
     * @throws Exception
     */
    public function __construct(Company $participant, Project $project, Staff $addedBy)
    {
        $this->project                      = $project;
        $this->added                        = new DateTimeImmutable();
        $this->addedBy                      = $addedBy;
        $this->participant                  = $participant;
        $this->statuses                     = new ArrayCollection();
        $this->projectParticipationTranches = new ArrayCollection();
        $this->interestRequest              = new RangedOfferWithFee();
        $this->interestReply                = new Offer();
        $this->invitationRequest            = new OfferWithFee();
        $this->ndaSignatures                = new ArrayCollection();
        $this->projectParticipationMembers  = new ArrayCollection();

        $this->setCurrentStatus(new ProjectParticipationStatus($this, ProjectParticipationStatus::STATUS_CREATED, $addedBy));
    }

    public static function getFileTypes(): array
    {
        return [static::PROJECT_PARTICIPATION_FILE_TYPE_NDA];
    }

    public static function getPossibleInvitationReplyMode(): array
    {
        return static::getConstants('INVITATION_REPLY_MODE_');
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getParticipant(): Company
    {
        return $this->participant;
    }

    public function getCurrentStatus(): ?ProjectParticipationStatus
    {
        return $this->currentStatus;
    }

    /**
     * @param ProjectParticipationStatus|StatusInterface $currentStatus
     */
    public function setCurrentStatus(StatusInterface $currentStatus): ProjectParticipation
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    /**
     * @Groups({ProjectParticipation::SERIALIZER_GROUP_ADMIN_READ})
     */
    public function getParticipantLastConsulted(): ?DateTimeImmutable
    {
        return $this->participantLastConsulted;
    }

    public function setParticipantLastConsulted(?DateTimeImmutable $participantLastConsulted): ProjectParticipation
    {
        $this->participantLastConsulted = $participantLastConsulted;

        return $this;
    }

    public function getCommitteeDeadline(): ?DateTimeImmutable
    {
        return $this->committeeDeadline;
    }

    public function setCommitteeDeadline(?DateTimeImmutable $committeeDeadline): ProjectParticipation
    {
        $this->committeeDeadline = $committeeDeadline;

        return $this;
    }

    public function getCommitteeComment(): ?string
    {
        return $this->committeeComment;
    }

    public function setCommitteeComment(?string $committeeComment): ProjectParticipation
    {
        $this->committeeComment = $committeeComment;

        return $this;
    }

    public function getInterestRequest(): RangedOfferWithFee
    {
        return $this->interestRequest;
    }

    public function setInterestRequest(RangedOfferWithFee $interestRequest): ProjectParticipation
    {
        $this->interestRequest = $interestRequest;

        return $this;
    }

    public function getInterestReply(): Offer
    {
        return $this->interestReply;
    }

    public function setInterestReply(Offer $interestReply): ProjectParticipation
    {
        $this->interestReply = $interestReply;

        return $this;
    }

    public function getInvitationRequest(): OfferWithFee
    {
        return $this->invitationRequest;
    }

    public function setInvitationRequest(OfferWithFee $invitationRequest): ProjectParticipation
    {
        $this->invitationRequest = $invitationRequest;

        return $this;
    }

    public function getInvitationReplyMode(): ?string
    {
        return $this->invitationReplyMode;
    }

    public function setInvitationReplyMode(?string $invitationReplyMode): ProjectParticipation
    {
        $this->invitationReplyMode = $invitationReplyMode;

        return $this;
    }

    public function getAllocationFeeRate(): ?string
    {
        return $this->allocationFeeRate;
    }

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

    public function addProjectParticipationMember(ProjectParticipationMember $projectParticipationMember): ProjectParticipation
    {
        if (false === $this->projectParticipationMembers->contains($projectParticipationMember)) {
            $this->projectParticipationMembers->add($projectParticipationMember);
        }

        return $this;
    }

    /**
     * @return Collection|ProjectParticipationStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    public function isActive(): bool
    {
        return $this->getCurrentStatus() && 0 < $this->getCurrentStatus()->getStatus();
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
     */
    public function getTotalAllocation(): MoneyInterface
    {
        $totalAllocation = new NullableMoney();
        foreach ($this->projectParticipationTranches as $projectParticipationTranche) {
            $totalAllocation = MoneyCalculator::add($totalAllocation, $projectParticipationTranche->getAllocation()->getMoney());
        }

        return $totalAllocation;
    }

    public function getNda(): ?File
    {
        return $this->nda;
    }

    public function setNda(?File $nda): ProjectParticipation
    {
        $this->nda = $nda;

        return $this;
    }

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

    public function hasProjectParticipationTranche(ProjectParticipationTranche $projectParticipationTranche): bool
    {
        return $this->projectParticipationTranches->contains($projectParticipationTranche);
    }

    public function isArrangerParticipation(): bool
    {
        return $this->getParticipant() === $this->getProject()->getArranger();
    }

    /**
     * @return Collection|InterestReplyVersion[]
     */
    public function getInterestReplyVersions(): Collection
    {
        return $this->interestReplyVersions;
    }

    /**
     * @Groups({"projectParticipationMember:read"})
     */
    public function getAcceptableNdaVersion(): ?FileVersion
    {
        $file = $this->getNda() ?? $this->getProject()->getNda();

        return $file ? $file->getCurrentFileVersion() : null;
    }

    /**
     * @return ArrayCollection|NDASignature[]
     */
    public function getNDASignatures(): iterable
    {
        return $this->ndaSignatures;
    }

    /**
     * @Assert\Callback
     */
    public function validateSendingInvitation(ExecutionContextInterface $context): void
    {
        if ($this->getProject()->hasCompletedStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION)) {
            if ((null === $this->getInvitationRequest() || false === $this->invitationRequest->isValid())) {
                $context->buildViolation('Syndication.ProjectParticipation.invitationRequest.invalid')
                    ->atPath('invitationRequest')
                    ->addViolation()
                ;
            }

            if ($this->projectParticipationTranches->isEmpty() && $this->getCurrentStatus()->getStatus() > 0) {
                $context->buildViolation('Syndication.ProjectParticipation.projectParticipationTranches.required')
                    ->atPath('projectParticipationTranches')
                    ->addViolation()
                ;
            }
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateProjectParticipationTranches(ExecutionContextInterface $context): void
    {
        foreach ($this->projectParticipationTranches as $index => $participationTranche) {
            if ($participationTranche->getProjectParticipation() !== $this) {
                $context->buildViolation('Syndication.ProjectParticipation.projectParticipationTranches.incorrectParticipation')
                    ->atPath("projectParticipationTranches[{$index}]")
                    ->addViolation()
                ;
            }
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateProjectParticipationMembers(ExecutionContextInterface $context): void
    {
        foreach ($this->projectParticipationMembers as $index => $participationMember) {
            if ($participationMember->getProjectParticipation() !== $this) {
                $context->buildViolation('Syndication.ProjectParticipation.projectParticipationMembers.incorrectParticipation')
                    ->atPath("projectParticipationMembers[{$index}]")
                    ->addViolation()
                ;
            }
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateCommitteeDeadline(ExecutionContextInterface $context): void
    {
        if (null === $this->committeeDeadline && ProjectParticipationStatus::STATUS_COMMITTEE_PENDED === $this->currentStatus->getStatus()) {
            $context->buildViolation('Syndication.ProjectParticipation.committeeDeadline.required')
                ->atPath('committeeDeadline')
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateCurrencyConsistency(ExecutionContextInterface $context): void
    {
        $globalFundingMoney = $this->getProject()->getGlobalFundingMoney();

        if (MoneyCalculator::isDifferentCurrency($this->interestRequest->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Core.Money.currency.inconsistent')
                ->atPath('interestRequest.money')
                ->addViolation()
            ;
        }

        if (MoneyCalculator::isDifferentCurrency($this->interestRequest->getMaxMoney(), $globalFundingMoney)) {
            $context->buildViolation('Core.Money.currency.inconsistent')
                ->atPath('interestRequest.maxMoney')
                ->addViolation()
            ;
        }

        if (MoneyCalculator::isDifferentCurrency($this->interestReply->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Core.Money.currency.inconsistent')
                ->atPath('interestReply')
                ->addViolation()
            ;
        }

        if (MoneyCalculator::isDifferentCurrency($this->invitationRequest->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Core.Money.currency.inconsistent')
                ->atPath('invitationRequest')
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateMaxMoney(ExecutionContextInterface $context): void
    {
        $interestMaxAmount = $this->interestRequest->getMaxMoney();
        if (null !== $interestMaxAmount->getAmount() && 1 !== MoneyCalculator::compare($interestMaxAmount, $this->interestRequest->getMoney())) {
            $context->buildViolation('Core.Money.currency.maxMoney')
                ->atPath('interestRequest.maxMoney')
                ->addViolation()
            ;
        }
    }
}
