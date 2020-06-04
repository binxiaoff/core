<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiProperty, ApiResource, ApiSubresource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\{NumericFilter, SearchFilter};
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;
use Unilend\Entity\{Embeddable\Money, Traits\TimestampableTrait, Traits\TraceableStatusTrait};
use Unilend\Filter\ArrayFilter;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"project:read", "company:read", "marketSegment:read", "projectParticipation:read", "projectParticipationOffer:read", "money:read"}},
 *     denormalizationContext={"groups": {"project:write", "company:write", "money:write", "tag:write"}},
 *     collectionOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "project:list",
 *                     "project:read",
 *                     "company:read",
 *                     "marketSegment:read",
 *                     "projectParticipation:read",
 *                     "projectParticipationOffer:read",
 *                     "money:read"
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
 *                     "tag:write"
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
 *                 "projectParticipationOffer:read",
 *                 "money:read",
 *                 "file:read",
 *                 "fileVersion:read",
 *                 "projectStatus:read",
 *                 "projectParticipationContact:read",
 *                 "projectParticipationFee:read",
 *                 "projectOrganizer:read",
 *                 "tranche_project:read",
 *                 "trancheFee:read",
 *                 "tranche:read",
 *                 "role:read",
 *                 "client:read",
 *                 "timestampable:read",
 *                 "traceableStatus:read",
 *                 "nullableLendingRate:read",
 *                 "lendingRate:read",
 *                 "fee:read",
 *                 "tag:read"
 *             }}
 *         },
 *         "project_confidentiality": {
 *             "method": "GET",
 *             "security": "is_granted('view_confidentiality_document', object)",
 *             "normalization_context": {"groups": {"project:confidentiality:read", "file:read"}},
 *             "path": "/projects/{id}/confidentiality"
 *         },
 *         "patch": {
 *             "security_post_denormalize": "is_granted('edit', previous_object)",
 *             "denormalization_context": {"groups": {"project:update", "projectStatus:create", "project:write", "company:write", "money:write", "tag:write"}}
 *         }
 *     }
 * )
 *
 * @ApiFilter(NumericFilter::class, properties={"currentStatus.status"})
 * @ApiFilter(ArrayFilter::class, properties={"organizers.roles"})
 * @ApiFilter(SearchFilter::class, properties={"submitterCompany.publicId"})
 *
 * @ORM\Table(indexes={
 *     @ORM\Index(name="hash", columns={"hash"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProject")
 *
 * @method ProjectStatus getCurrentStatus
 */
class Project
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use TraceableStatusTrait {
        setCurrentStatus as private baseStatusSetter;
    }

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

    public const PROJECT_FILE_TYPE_DESCRIPTION     = 'project_file_description';
    public const PROJECT_FILE_TYPE_CONFIDENTIALITY = 'project_file_confidentiality';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @ApiProperty(identifier=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(length=36)
     *
     * @ApiProperty(identifier=true)
     *
     * @Groups({"project:read"})
     */
    private $hash;

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
     * @Assert\Valid
     * @Assert\Length(max="255")
     */
    private $riskGroupName;

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
     * @Assert\Valid
     */
    private $submitterCompany;

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
    private $submitterClient;

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
    private $title;

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
    private $marketSegment;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $description;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File", orphanRemoval=true)
     * @ORM\JoinColumn(name="id_description_document", unique=true)
     *
     * @Groups({"project:write", "project:read"})
     */
    private $descriptionDocument;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File", orphanRemoval=true)
     * @ORM\JoinColumn(name="id_confidentiality_disclaimer", unique=true)
     *
     * @Groups({"project:write", "project:read"})
     */
    private $confidentialityDisclaimer;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $confidential = false;

    /**
     * en front (barre de progression projet) : Signature.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $signingDeadline;

    /**
     * en front (barre de progression projet) : Allocation.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $allocationDeadline;

    /**
     * en front (barre de progression projet) : Réponse ferme.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $participantReplyDeadline;

    /**
     * en front (barre de progression projet) : Marque d'interet.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $interestExpressionDeadline;

    /**
     * en front (barre de progression projet) : Projet de contrat.
     *
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $contractualizationDeadline;

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
    private $internalRatingScore;

    /**
     * @var int
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
    private $offerVisibility;

    /**
     * @var ProjectParticipation[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipation", mappedBy="project", cascade={"persist"}, orphanRemoval=true, fetch="EAGER")
     *
     * @MaxDepth(2)
     *
     * @Groups({"project:admin:read"})
     *
     * @ApiSubresource
     */
    private $projectParticipations;

    /**
     * @var ProjectOrganizer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectOrganizer", mappedBy="project", cascade={"persist"})
     */
    private $organizers;

    /**
     * @var ProjectComment[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectComment", mappedBy="project")
     * @ORM\OrderBy({"added": "DESC"})
     *
     * @Groups({"project:read"})
     */
    private $comments;

    /**
     * @var Tranche[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Tranche", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @Assert\Valid
     *
     * @Groups({"project:read"})
     */
    private $tranches;

    /**
     * @var ProjectStatus
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectStatus")
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"project:read", "project:update"})
     */
    private $currentStatus;

    /**
     * @var ArrayCollection|ProjectStatus
     *
     * @Assert\Count(min="1")
     * @Assert\Valid
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectStatus", mappedBy="project", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     */
    private $statuses;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getSyndicationTypes")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $syndicationType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getParticipationTypes")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private $participationType;

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
    private $riskType;

    /**
     * @var Collection|Tag[]
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Entity\Tag", cascade={"persist"})
     *
     * @Groups({"project:read", "project:write"})
     */
    private $tags;

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
    private $globalFundingMoney;

    /**
     * @var ProjectFile[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ProjectFile", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @ApiSubresource
     */
    private $projectFiles;

    /**
     * @var bool
     *
     * @Groups({"project:read", "project:create"})
     *
     * @ORM\Column(type="boolean")
     */
    private $interestExpressionEnabled;

    /**
     * @param Staff         $addedBy
     * @param Company       $borrowerCompany
     * @param Money         $globalFundingMoney
     * @param MarketSegment $marketSegment
     *
     * @throws Exception
     */
    public function __construct(Staff $addedBy, Company $borrowerCompany, Money $globalFundingMoney, MarketSegment $marketSegment)
    {
        $this->projectFiles          = new ArrayCollection();
        $this->projectParticipations = new ArrayCollection();
        $this->comments              = new ArrayCollection();
        $this->statuses              = new ArrayCollection();
        $this->tranches              = new ArrayCollection();
        $this->tags                  = new ArrayCollection();
        $this->organizers            = new ArrayCollection();
        $this->added                 = new DateTimeImmutable();
        $this->marketSegment         = $marketSegment;
        $this->submitterClient       = $addedBy->getClient();
        $this->submitterCompany      = $addedBy->getCompany();

        $this->setCurrentStatus(new ProjectStatus($this, ProjectStatus::STATUS_REQUESTED, $addedBy));

        $this->syndicationType   = static::PROJECT_SYNDICATION_TYPE_PRIMARY;
        $this->participationType = static::PROJECT_PARTICIPATION_TYPE_DIRECT;
        $this->offerVisibility   = static::OFFER_VISIBILITY_PRIVATE;

        $this->riskGroupName      = $borrowerCompany;
        $this->globalFundingMoney = $globalFundingMoney;

        if (null === $this->hash) {
            try {
                $this->hash = (string) (Uuid::uuid4());
            } catch (Throwable $e) {
                $this->hash = md5(uniqid('', false));
            }
        }

        $arranger = new ProjectOrganizer($this->submitterCompany, $this, $addedBy, [ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER]);
        $this->organizers->add($arranger);

        $participant = new ProjectParticipation($this->submitterCompany, $this, $addedBy);
        $this->projectParticipations->add($participant);

        $this->interestExpressionEnabled = false;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
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
    public function setConfidentialityDisclaimer(?File $file): self
    {
        $this->confidentialityDisclaimer = $file;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getConfidentialityDisclaimer(): ?File
    {
        return $this->confidentialityDisclaimer;
    }

    /**
     * @return bool
     */
    public function isConfidential(): bool
    {
        return $this->confidential;
    }

    /**
     * @param bool $confidential
     *
     * @return Project
     */
    public function setConfidential(bool $confidential): Project
    {
        $this->confidential = $confidential;

        return $this;
    }

    /**
     * TODO its argument should be an int not the object itself.
     *
     * @param ProjectStatus $projectStatus
     *
     * @return Project
     */
    public function setCurrentStatus(ProjectStatus $projectStatus): self
    {
        $projectStatus->setProject($this);

        return $this->baseStatusSetter($projectStatus);
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
     * @throws Exception
     *
     * @return ProjectOrganizer|null
     */
    public function getLoanOfficer(): ?ProjectOrganizer
    {
        return $this->getUniqueOrganizer(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_LOAN_OFFICER);
    }

    /**
     * @throws Exception
     *
     * @return ProjectOrganizer|null
     */
    public function getSecurityTrustee(): ?ProjectOrganizer
    {
        return $this->getUniqueOrganizer(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_SECURITY_TRUSTEE);
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
    public function getComments(): iterable
    {
        return $this->comments;
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
     * @return bool
     */
    public function isEditable(): bool
    {
        if ($this->getCurrentStatus()) {
            return $this->getCurrentStatus()->getStatus() < ProjectStatus::STATUS_PUBLISHED;
        }

        return true;
    }

    /**
     * @throws Exception
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        return ProjectStatus::STATUS_PUBLISHED === $this->getCurrentStatus()->getStatus();
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
     * @return string
     */
    public function getSyndicationType(): string
    {
        return $this->syndicationType;
    }

    /**
     * @param string $syndicationType
     *
     * @return Project
     */
    public function setSyndicationType(string $syndicationType): Project
    {
        $this->syndicationType = $syndicationType;

        return $this;
    }

    /**
     * @return string
     */
    public function getParticipationType(): string
    {
        return $this->participationType;
    }

    /**
     * @param string $participationType
     *
     * @return Project
     */
    public function setParticipationType(string $participationType): Project
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
            $money = $money->add($tranche->getMoney());
        }

        return $money;
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
        $trancheAmounts = $this->tranches->map(static function (Tranche $tranche) {
            return $tranche->getMoney();
        });

        return array_reduce(
            $trancheAmounts->toArray(),
            static function (Money $carry, Money $item) {
                return $carry->add($item);
            },
            new Money('EUR')
        );
    }

    /**
     * @throws Exception
     *
     * @return Money
     *
     * @Groups({"project:read"})
     */
    public function getOffersMoney(): Money
    {
        $money = new Money($this->getGlobalFundingMoney()->getCurrency());

        foreach ($this->getProjectParticipations() as $projectParticipation) {
            $money = $projectParticipation->getOfferMoney() ? $money->add($projectParticipation->getOfferMoney()) : $money;
        }

        return $money;
    }

    /**
     * @return array|string[]
     *
     * @Groups({"project:read"})
     */
    public function getAvailableOrganiserRoles(): array
    {
        return array_values(
            array_filter(
                ProjectOrganizer::getAvailableRoles(),
                function (string $role) {
                    $isUnique = ProjectOrganizer::isUniqueRole($role);

                    return ($isUnique && (0 === count($this->getOrganizersByRole($role)))) || false === $isUnique;
                }
            )
        );
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
        $this->interestExpressionEnabled = $interestExpressionEnabled;

        return $this;
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
