<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource, ApiSubresource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\{NumericFilter, SearchFilter};
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\{Embeddable\Money, Embeddable\NullableMoney, Embeddable\NullablePerson, Interfaces\StatusInterface, Interfaces\TraceableStatusAwareInterface,
    Traits\PublicizeIdentityTrait, Traits\TimestampableTrait};
use Unilend\Filter\ArrayFilter;
use Unilend\Service\MoneyCalculator;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "project:read",
 *             "company:read",
 *             "projectParticipation:read",
 *             "projectParticipationTranche:read",
 *             "money:read",
 *             "nullableMoney:read",
 *             "nullablePerson:read",
 *             "projectStatus:read",
 *             "projectOrganizer:read",
 *             "role:read"
 *         }
 *     },
 *     denormalizationContext={"groups": {"project:write", "company:write", "money:write", "tag:write", "nullablePerson:write"}},
 *     collectionOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "project:list",
 *                     "project:read",
 *                     "company:read",
 *                     "projectParticipation:read",
 *                     "projectParticipationTranche:read",
 *                     "money:read",
 *                     "nullableMoney:read",
 *                     "nullablePerson:read"
 *                 }
 *             }
 *         },
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "project:create",
 *                     "project:write",
 *                     "company:write",
 *                     "money:write",
 *                     "nullableMoney:write",
 *                     "tag:write",
 *                     "nullablePerson:write"
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {"groups": {
 *                 "project:read",
 *                 "company:read",
 *                 "projectParticipation:read",
 *                 "projectParticipationTranche:read",
 *                 "projectParticipationStatus:read",
 *                 "money:read",
 *                 "file:read",
 *                 "fileVersion:read",
 *                 "projectStatus:read",
 *                 "projectParticipationMember:read",
 *                 "archivable:read",
 *                 "projectOrganizer:read",
 *                 "tranche_project:read",
 *                 "tranche:read",
 *                 "role:read",
 *                 "client:read",
 *                 "timestampable:read",
 *                 "traceableStatus:read",
 *                 "nullableLendingRate:read",
 *                 "lendingRate:read",
 *                 "fee:read",
 *                 "tag:read",
 *                 "nullablePerson:read",
 *                 "nullableMoney:read",
 *                 "rangedOfferWithFee:read",
 *                 "offerWithFee:read",
 *                 "offer:read",
 *                 "companyStatus:read"
 *             }}
 *         },
 *         "project_nda": {
 *             "method": "GET",
 *             "security": "is_granted('view_nda', object)",
 *             "normalization_context": {"groups": {"project:nda:read", "file:read"}},
 *             "path": "/projects/{id}/nda"
 *         },
 *         "patch": {
 *             "security_post_denormalize": "is_granted('edit', previous_object)",
 *             "denormalization_context": {
 *                 "groups": {"project:update", "projectStatus:create", "project:write", "company:write", "money:write", "nullableMoney:write", "tag:write", "nullablePerson:write"}
 *             }
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
 *         }
 *     }
 * )
 *
 * @ApiFilter(NumericFilter::class, properties={"currentStatus.status"})
 * @ApiFilter(ArrayFilter::class, properties={"organizers.roles"})
 * @ApiFilter(SearchFilter::class, properties={"submitterCompany.publicId"})
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProject")
 */
class Project implements TraceableStatusAwareInterface
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use PublicizeIdentityTrait;

    public const OFFER_VISIBILITY_PRIVATE     = 'private';
    public const OFFER_VISIBILITY_PARTICIPANT = 'participant';
    public const OFFER_VISIBILITY_PUBLIC      = 'public';

    public const INTERNAL_RATING_SCORE_A_PLUS  = 'A+';
    public const INTERNAL_RATING_SCORE_A       = 'A';
    public const INTERNAL_RATING_SCORE_B_PLUS  = 'B+';
    public const INTERNAL_RATING_SCORE_B       = 'B';
    public const INTERNAL_RATING_SCORE_C_PLUS  = 'C+';
    public const INTERNAL_RATING_SCORE_C       = 'C';
    public const INTERNAL_RATING_SCORE_C_MINUS = 'C-';
    public const INTERNAL_RATING_SCORE_D_PLUS  = 'D+';
    public const INTERNAL_RATING_SCORE_D       = 'D';
    public const INTERNAL_RATING_SCORE_D_MINUS = 'D-';
    public const INTERNAL_RATING_SCORE_E_PLUS  = 'E+';
    public const INTERNAL_RATING_SCORE_E       = 'E';
    public const INTERNAL_RATING_SCORE_E_MINUS = 'E-';
    public const INTERNAL_RATING_SCORE_F       = 'F';
    public const INTERNAL_RATING_SCORE_Z       = 'Z';

    public const PROJECT_SYNDICATION_TYPE_PRIMARY   = 'primary';
    public const PROJECT_SYNDICATION_TYPE_SECONDARY = 'secondary';

    public const PROJECT_PARTICIPATION_TYPE_DIRECT            = 'direct';
    public const PROJECT_PARTICIPATION_TYPE_SUB_PARTICIPATION = 'sub_participation';

    public const PROJECT_RISK_TYPE_RISK     = 'risk';
    public const PROJECT_RISK_TYPE_TREASURY = 'risk_treasury';

    public const SERIALIZER_GROUP_ADMIN_READ = 'project:admin:read'; // Additional group that is available for admin (admin user or arranger)

    public const FIELD_CURRENT_STATUS = 'currentStatus';
    public const FIELD_DESCRIPTION    = 'description';

    public const PROJECT_FILE_TYPE_DESCRIPTION = 'project_file_description';
    public const PROJECT_FILE_TYPE_NDA         = 'project_file_nda';

    public const FUNDING_SPECIFICITY_FSA = 'FSA';
    public const FUNDING_SPECIFICITY_LBO = 'LBO';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     *
     * @Assert\NotBlank
     * @Assert\Length(max="255")
     */
    private string $riskGroupName;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company_submitter", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"project:read"})
     *
     * @Assert\NotBlank
     */
    private Company $submitterCompany;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client_submitter",  referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private Clients $submitterClient;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     *
     * @Assert\NotBlank
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private string $title;

    /**
     * @var MarketSegment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MarketSegment")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_market_segment", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotBlank
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private MarketSegment $marketSegment;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private string $description;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File", orphanRemoval=true)
     * @ORM\JoinColumn(name="id_description_document", unique=true)
     *
     * @Groups({"project:write", "project:read"})
     */
    private File $descriptionDocument;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File", orphanRemoval=true)
     * @ORM\JoinColumn(name="id_nda", unique=true)
     *
     * @Groups({"project:write", "project:read"})
     */
    private File $nda;

    /**
     * en front (barre de progression projet) : Signature.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?DateTimeImmutable $signingDeadline;

    /**
     * en front (barre de progression projet) : Allocation.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?DateTimeImmutable $allocationDeadline;

    /**
     * en front (barre de progression projet) : Réponse ferme.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?DateTimeImmutable $participantReplyDeadline;

    /**
     * en front (barre de progression projet) : Marque d'interet.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?DateTimeImmutable $interestExpressionDeadline;

    /**
     * en front (barre de progression projet) : Projet de contrat.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?DateTimeImmutable $contractualizationDeadline;

    /**
     * @var string|null
     *
     * @ORM\Column(length=8, nullable=true)
     *
     * @Assert\Choice(callback="getInternalRatingScores")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?string $internalRatingScore;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false, length=25)
     *
     * @Gedmo\Versioned
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getOfferVisibilities")
     *
     * @Groups({"project:write", "project:read"})
     */
    private string $offerVisibility;

    /**
     * @var ProjectParticipation[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipation", mappedBy="project", cascade={"persist"}, orphanRemoval=true, fetch="EAGER")
     *
     * @MaxDepth(2)
     *
     * @Groups({"project:admin:read"})
     *
     * @ApiSubresource
     */
    private Collection $projectParticipations;

    /**
     * @var ProjectOrganizer[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectOrganizer", mappedBy="project", cascade={"persist"})
     */
    private Collection $organizers;

    /**
     * @var ProjectComment[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectComment", mappedBy="project")
     * @ORM\OrderBy({"added": "DESC"})
     *
     * @Groups({"project:read"})
     */
    private Collection $projectComments;

    /**
     * @var Tranche[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Tranche", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @Assert\Valid
     *
     * @Groups({"project:read"})
     */
    private Collection $tranches;

    /**
     * @var ProjectStatus
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"project:read", "project:update"})
     */
    private ProjectStatus $currentStatus;

    /**
     * @var ProjectStatus[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectStatus", mappedBy="project", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"project:read"})
     */
    private Collection $statuses;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Choice(callback="getSyndicationTypes")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?string $syndicationType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Choice(callback="getParticipationTypes")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?string $participationType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, length=80)
     *
     * @Assert\Expression("(!this.isSubParticipation() and !value) or (this.isSubParticipation() and value)")
     * @Assert\Choice(callback="getRiskTypes")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private string $riskType;

    /**
     * @var Collection|Tag[]
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Entity\Tag", cascade={"persist"})
     *
     * @Groups({"project:read", "project:write"})
     */
    private Collection $tags;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Money")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"project:read", "project:write"})
     */
    private Money $globalFundingMoney;

    /**
     * @var ProjectFile[]|Collection
     *
     * @ORM\OneToMany(targetEntity="ProjectFile", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @ApiSubresource
     */
    private Collection $projectFiles;

    /**
     * @var bool
     *
     * @Groups({"project:read", "project:write"})
     *
     * @ORM\Column(type="boolean")
     */
    private bool $interestExpressionEnabled;

    /**
     * @var NullableMoney
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"project:admin:read", "project:write"})
     */
    private NullableMoney $arrangementCommissionMoney;

    /**
     * @var string|null
     *
     * @Groups({"project:read", "project:write"})
     *
     * @ORM\Column(type="string", nullable=true, length=10)
     *
     * @Assert\Choice({Project::FUNDING_SPECIFICITY_FSA, Project::FUNDING_SPECIFICITY_LBO})
     */
    private string $fundingSpecificity;

    /**
     * @var NullablePerson
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullablePerson", columnPrefix="privileged_contact_")
     *
     * @Assert\Valid
     *
     * @Groups({"project:read", "project:write"})
     */
    private NullablePerson $privilegedContactPerson;

    /**
     * @param Staff         $addedBy
     * @param string        $riskGroupName
     * @param Money         $globalFundingMoney
     * @param MarketSegment $marketSegment
     *
     * @throws Exception
     */
    public function __construct(Staff $addedBy, string $riskGroupName, Money $globalFundingMoney, MarketSegment $marketSegment)
    {
        $this->projectFiles          = new ArrayCollection();
        $this->projectParticipations = new ArrayCollection();
        $this->projectComments       = new ArrayCollection();
        $this->statuses              = new ArrayCollection();
        $this->tranches              = new ArrayCollection();
        $this->tags                  = new ArrayCollection();
        $this->organizers            = new ArrayCollection();
        $this->added                 = new DateTimeImmutable();
        $this->marketSegment         = $marketSegment;
        $this->submitterClient       = $addedBy->getClient();
        $this->submitterCompany      = $addedBy->getCompany();

        $this->setCurrentStatus(new ProjectStatus($this, ProjectStatus::STATUS_DRAFT, $addedBy));

        $this->offerVisibility    = static::OFFER_VISIBILITY_PRIVATE;
        $this->riskGroupName      = $riskGroupName;
        $this->globalFundingMoney = $globalFundingMoney;

        $arranger = new ProjectOrganizer($this->submitterCompany, $this, $addedBy, [ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER]);
        $this->organizers->add($arranger);

        $this->interestExpressionEnabled  = false;
        $this->arrangementCommissionMoney = new NullableMoney();
    }

    /**
     * @param string $riskGroupName
     *
     * @return Project
     */
    public function setRiskGroupName(string $riskGroupName): Project
    {
        $this->riskGroupName = $riskGroupName;

        return $this;
    }

    /**
     * @return Company
     */
    public function getRiskGroupName(): string
    {
        return $this->riskGroupName;
    }

    /**
     * @return Company
     */
    public function getSubmitterCompany(): Company
    {
        return $this->submitterCompany;
    }

    /**
     * @return Clients
     */
    public function getSubmitterClient(): Clients
    {
        return $this->submitterClient;
    }

    /**
     * @param string $title
     *
     * @return Project
     */
    public function setTitle($title): Project
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $description
     *
     * @return Project
     */
    public function setDescription(?string $description): Project
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param File|null $file
     *
     * @return Project
     */
    public function setDescriptionDocument(?File $file): self
    {
        $this->descriptionDocument = $file;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getDescriptionDocument(): ?File
    {
        return $this->descriptionDocument;
    }

    /**
     * @param File|null $file
     *
     * @return Project
     */
    public function setNda(?File $file): self
    {
        $this->nda = $file;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getNda(): ?File
    {
        return $this->nda;
    }

    /**
     * @return StatusInterface|ProjectStatus
     */
    public function getCurrentStatus()
    {
        return $this->currentStatus;
    }

    /**
     * @param ProjectStatus|StatusInterface $projectStatus
     *
     * @return Project
     */
    public function setCurrentStatus(StatusInterface $projectStatus): Project
    {
        if ($projectStatus->getAttachedObject() !== $this) {
            throw new RuntimeException('Attempt to add an incorrect status');
        }

        $this->currentStatus = $projectStatus;

        return $this;
    }

    /**
     * @return Collection|StatusInterface[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    /**
     * @return MarketSegment
     */
    public function getMarketSegment(): MarketSegment
    {
        return $this->marketSegment;
    }

    /**
     * @param MarketSegment $marketSegment
     *
     * @return Project
     */
    public function setMarketSegment(MarketSegment $marketSegment): Project
    {
        $this->marketSegment = $marketSegment;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getSigningDeadline(): ?DateTimeImmutable
    {
        return $this->signingDeadline;
    }

    /**
     * @param DateTimeImmutable|null $signingDeadline
     *
     * @return Project
     */
    public function setSigningDeadline(?DateTimeImmutable $signingDeadline): Project
    {
        $this->signingDeadline = $signingDeadline;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getAllocationDeadline(): ?DateTimeImmutable
    {
        return $this->allocationDeadline;
    }

    /**
     * @param DateTimeImmutable|null $allocationDeadline
     *
     * @return Project
     */
    public function setAllocationDeadline(?DateTimeImmutable $allocationDeadline): Project
    {
        $this->allocationDeadline = $allocationDeadline;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getParticipantReplyDeadline(): ?DateTimeImmutable
    {
        return $this->participantReplyDeadline;
    }

    /**
     * @param DateTimeImmutable|null $participantReplyDeadline
     *
     * @return Project
     */
    public function setParticipantReplyDeadline(?DateTimeImmutable $participantReplyDeadline): Project
    {
        $this->participantReplyDeadline = $participantReplyDeadline;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInternalRatingScore(): ?string
    {
        return $this->internalRatingScore;
    }

    /**
     * @param string|null $internalRatingScore
     */
    public function setInternalRatingScore(?string $internalRatingScore): void
    {
        $this->internalRatingScore = $internalRatingScore;
    }

    /**
     * @return array
     */
    public function getInternalRatingScores(): array
    {
        return self::getConstants('INTERNAL_RATING_SCORE_');
    }

    /**
     * @return string
     */
    public function getOfferVisibility(): string
    {
        return $this->offerVisibility;
    }

    /**
     * @param string $offerVisibility
     *
     * @return Project
     */
    public function setOfferVisibility(string $offerVisibility): Project
    {
        $this->offerVisibility = $offerVisibility;

        return $this;
    }

    /**
     * @return iterable
     */
    public static function getOfferVisibilities(): iterable
    {
        return self::getConstants('OFFER_VISIBILITY_');
    }

    /**
     * @return ProjectFile[]|Collection
     */
    public function getProjectFiles(): Collection
    {
        return $this->projectFiles;
    }

    /**
     * @param ProjectFile $projectFile
     *
     * @return Project
     */
    public function removeProjectFile(ProjectFile $projectFile): Project
    {
        $this->projectFiles->removeElement($projectFile);

        return $this;
    }

    /**
     * @return ProjectParticipation[]|Collection
     */
    public function getProjectParticipations(): Collection
    {
        return $this->projectParticipations;
    }

    /**
     * @param Company $company
     *
     * @return ProjectParticipation|null
     */
    public function getProjectParticipationByCompany(Company $company): ?ProjectParticipation
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq('company', $company));

        // A company can only have one Participation on a project.
        return $this->projectParticipations->matching($criteria)->first() ?: null;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return Project
     */
    public function removeProjectParticipation(ProjectParticipation $projectParticipation): Project
    {
        $this->projectParticipations->removeElement($projectParticipation);

        return $this;
    }

    /**
     * @return ProjectOrganizer|null
     *
     * @Groups({"project:read"})
     *
     * @MaxDepth(1)
     */
    public function getArranger(): ?ProjectOrganizer
    {
        return $this->getUniqueOrganizer(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER);
    }

    /**
     * @throws Exception
     *
     * @return Collection|ProjectOrganizer[]
     */
    public function getDeputyArranger(): Collection
    {
        return $this->getOrganizersByRole(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_DEPUTY_ARRANGER);
    }

    /**
     * @throws Exception
     *
     * @return ProjectOrganizer|null
     */
    public function getRun(): ?ProjectOrganizer
    {
        return $this->getUniqueOrganizer(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_RUN);
    }

    /**
     * @return Collection|ProjectOrganizer[]
     *
     * @Groups({"project:read"})
     *
     * @MaxDepth(2)
     */
    public function getOrganizers(): Collection
    {
        return $this->organizers;
    }

    /**
     * @return ProjectComment[]|ArrayCollection
     */
    public function getProjectComments(): iterable
    {
        return $this->projectComments;
    }

    /**
     * @return Tranche[]|ArrayCollection
     */
    public function getTranches(): iterable
    {
        return $this->tranches;
    }

    /**
     * @param Tranche $tranche
     *
     * @return Project
     */
    public function addTranche(Tranche $tranche): Project
    {
        $tranche->setProject($this);

        if (false === $this->tranches->contains($tranche)) {
            $this->tranches->add($tranche);
        }

        return $this;
    }

    /**
     * @param Tranche $tranche
     *
     * @return Project
     */
    public function removeTranche(Tranche $tranche): Project
    {
        $this->tranches->removeElement($tranche);

        return $this;
    }

    /**
     * @return array|string[]
     */
    public static function getSyndicationTypes(): array
    {
        return static::getConstants('PROJECT_SYNDICATION_TYPE_');
    }

    /**
     * @return array|string[]
     */
    public static function getParticipationTypes(): array
    {
        return static::getConstants('PROJECT_PARTICIPATION_TYPE_');
    }

    /**
     * @return array|string[]
     */
    public static function getRiskTypes(): array
    {
        return static::getConstants('PROJECT_RISK_TYPE_');
    }

    /**
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->syndicationType === static::PROJECT_SYNDICATION_TYPE_PRIMARY;
    }

    /**
     * @return bool
     */
    public function isSecondary(): bool
    {
        return $this->syndicationType === static::PROJECT_SYNDICATION_TYPE_SECONDARY;
    }

    /**
     * @return bool
     */
    public function isDirect(): bool
    {
        return $this->participationType === static::PROJECT_PARTICIPATION_TYPE_DIRECT;
    }

    /**
     * @return bool
     */
    public function isSubParticipation(): bool
    {
        return $this->participationType === static::PROJECT_PARTICIPATION_TYPE_SUB_PARTICIPATION;
    }

    /**
     * @return bool
     */
    public function isRisk(): bool
    {
        return $this->isSubParticipation() && $this->riskType === static::PROJECT_RISK_TYPE_RISK;
    }

    /**
     * @return bool
     */
    public function isRiskAndTreasury(): bool
    {
        return $this->isSubParticipation() && $this->riskType === static::PROJECT_RISK_TYPE_TREASURY;
    }

    /**
     * @return string|null
     */
    public function getSyndicationType(): ?string
    {
        return $this->syndicationType;
    }

    /**
     * @param string|null $syndicationType
     *
     * @return Project
     */
    public function setSyndicationType(?string $syndicationType): Project
    {
        $this->syndicationType = $syndicationType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getParticipationType(): ?string
    {
        return $this->participationType;
    }

    /**
     * @param string|null $participationType
     *
     * @return Project
     */
    public function setParticipationType(?string $participationType): Project
    {
        $this->participationType = $participationType;

        if (false === $this->isSubParticipation()) {
            $this->riskType = null;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRiskType(): ?string
    {
        return $this->riskType;
    }

    /**
     * @param string|null $riskType
     *
     * @return Project
     */
    public function setRiskType(?string $riskType): Project
    {
        if (false === $this->isSubParticipation()) {
            $riskType = null;
        }

        $this->riskType = $riskType;

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag $tag
     *
     * @return Project
     */
    public function addTag(Tag $tag): Project
    {
        if (false === $this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * @param Tag $tag
     *
     * @return Project
     */
    public function removeTag(Tag $tag): Project
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * @return Money
     *
     * @Groups({"project:read"})
     */
    public function getTranchesTotalMoney(): Money
    {
        $money = new Money($this->getGlobalFundingMoney()->getCurrency());

        foreach ($this->getTranches() as $tranche) {
            $money = MoneyCalculator::add($money, $tranche->getMoney());
        }

        return $money;
    }

    /**
     * @return bool
     */
    public function isOversubscribed(): bool
    {
        $totalInvitationReplyAmount = new NullableMoney();

        foreach ($this->getTranches() as $tranche) {
            $totalInvitationReplyAmount = MoneyCalculator::add($totalInvitationReplyAmount, $tranche->getTotalInvitationReplyAmount());
        }

        return $totalInvitationReplyAmount->getAmount() > $this->getTranchesTotalMoney()->getAmount();
    }

    /**
     * @param Money $globalFundingMoney
     *
     * @return Project
     */
    public function setGlobalFundingMoney(Money $globalFundingMoney): Project
    {
        $this->globalFundingMoney = $globalFundingMoney;

        return $this;
    }

    /**
     * @return Money
     */
    public function getGlobalFundingMoney(): Money
    {
        return $this->globalFundingMoney;
    }

    /**
     * @return Money
     *
     * @Groups({"project:read"})
     */
    public function getSyndicatedAmount(): Money
    {
        $syndicatedMoney = new Money($this->getGlobalFundingMoney()->getCurrency());

        foreach ($this->getTranches() as $tranche) {
            if ($tranche->isSyndicated()) {
                $syndicatedMoney = MoneyCalculator::add($syndicatedMoney, $tranche->getMoney());
            }
        }

        return $syndicatedMoney;
    }

    /**
     * @return array
     */
    public static function getProjectFileTypes(): array
    {
        return self::getConstants('PROJECT_FILE_TYPE_');
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getInterestExpressionDeadline(): ?DateTimeImmutable
    {
        return $this->interestExpressionDeadline;
    }

    /**
     * @param DateTimeImmutable|null $interestExpressionDeadline
     *
     * @return Project
     */
    public function setInterestExpressionDeadline(?DateTimeImmutable $interestExpressionDeadline): Project
    {
        $this->interestExpressionDeadline = $interestExpressionDeadline;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getContractualizationDeadline(): ?DateTimeImmutable
    {
        return $this->contractualizationDeadline;
    }

    /**
     * @param DateTimeImmutable|null $contractualizationDeadline
     *
     * @return Project
     */
    public function setContractualizationDeadline(?DateTimeImmutable $contractualizationDeadline): Project
    {
        $this->contractualizationDeadline = $contractualizationDeadline;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInterestExpressionEnabled(): bool
    {
        return $this->interestExpressionEnabled;
    }

    /**
     * @param bool $interestExpressionEnabled
     *
     * @return Project
     */
    public function setInterestExpressionEnabled(bool $interestExpressionEnabled): Project
    {
        if (null === $this->getCurrentStatus() || ProjectStatus::STATUS_DRAFT === $this->getCurrentStatus()->getStatus()) {
            $this->interestExpressionEnabled = $interestExpressionEnabled;
        }

        return $this;
    }

    /**
     * @return NullableMoney|null
     */
    public function getArrangementCommissionMoney(): ?NullableMoney
    {
        return $this->arrangementCommissionMoney->isValid() ? $this->arrangementCommissionMoney : null;
    }

    /**
     * @param NullableMoney $arrangementCommissionMoney
     *
     * @return Project
     */
    public function setArrangementCommissionMoney(NullableMoney $arrangementCommissionMoney): Project
    {
        $this->arrangementCommissionMoney = $arrangementCommissionMoney;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFundingSpecificity(): ?string
    {
        return $this->fundingSpecificity;
    }

    /**
     * @param string|null $fundingSpecificity
     *
     * @return Project
     */
    public function setFundingSpecificity(?string $fundingSpecificity): Project
    {
        $this->fundingSpecificity = $fundingSpecificity;

        return $this;
    }

    /**
     * @return NullablePerson
     */
    public function getPrivilegedContactPerson(): NullablePerson
    {
        return $this->privilegedContactPerson;
    }

    /**
     * @param NullablePerson $privilegedContactPerson
     *
     * @return Project
     */
    public function setPrivilegedContactPerson(NullablePerson $privilegedContactPerson): Project
    {
        $this->privilegedContactPerson = $privilegedContactPerson;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->hasCompletedStatus(ProjectStatus::STATUS_DRAFT);
    }

    /**
     * @return bool
     */
    public function isInterestCollected(): bool
    {
        return $this->hasCompletedStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION);
    }

    /**
     * @return bool
     */
    public function isInInterestCollectionStep(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION);
    }

    /**
     * @return bool
     */
    public function isInOfferNegotiationStep(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_PARTICIPANT_REPLY);
    }

    /**
     * @return bool
     */
    public function isInAllocationStep(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_ALLOCATION);
    }

    /**
     * Used in an expression constraints.
     *
     * @return bool
     */
    public function isInContractNegotiationStep(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_CONTRACTUALISATION);
    }

    /**
     * @param int $testedStatus
     *
     * @return bool
     */
    public function hasCurrentStatus(int $testedStatus): bool
    {
        return $testedStatus === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @param int $testedStatus
     *
     * @return bool
     */
    public function hasCompletedStatus(int $testedStatus): bool
    {
        return $this->getCurrentStatus()->getStatus() > $testedStatus;
    }

    /**
     * @return bool
     */
    public function hasEditableStatus(): bool
    {
        return ProjectStatus::STATUS_SYNDICATION_CANCELLED !== $this->getCurrentStatus()->getStatus();
    }

    /**
     * @param string $role
     *
     * @return ProjectOrganizer[]|Collection
     */
    private function getOrganizersByRole(string $role): Collection
    {
        $organizers = new ArrayCollection();

        // Ugly foreach on the Organizer (hopefully we don't have many organisers on a project), as the Criteria doesn't support the json syntax.
        foreach ($this->getOrganizers() as $organizer) {
            if ($organizer->hasRole($role)) {
                $organizers->add($organizer);
            }
        }

        return $organizers;
    }

    /**
     * @param string $role
     *
     * @return ProjectOrganizer|null
     */
    private function getUniqueOrganizer(string $role): ?ProjectOrganizer
    {
        if (false === ProjectOrganizer::isUniqueRole($role)) {
            throw new RuntimeException(sprintf('Role "%s" is not unique. Cannot get project Participation corresponding to the role.', $role));
        }

        return $this->getOrganizersByRole($role)->first() ?: null;
    }
}
