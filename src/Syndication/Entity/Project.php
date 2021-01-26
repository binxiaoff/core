<?php

declare(strict_types=1);

namespace Unilend\Syndication\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource, ApiSubresource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\{NumericFilter, SearchFilter};
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\FundingSpecificity;
use Unilend\Core\Entity\Constant\SyndicationModality\ParticipationType;
use Unilend\Core\Entity\Constant\SyndicationModality\RiskType;
use Unilend\Core\Entity\Constant\SyndicationModality\SyndicationType;
use Unilend\Core\Entity\{Company,
    CompanyGroupTag,
    Embeddable\Money,
    Embeddable\NullableMoney,
    Embeddable\NullablePerson,
    File,
    Interfaces\MoneyInterface,
    Interfaces\StatusInterface,
    Interfaces\TraceableStatusAwareInterface,
    Staff,
    Traits\PublicizeIdentityTrait,
    Traits\TimestampableTrait,
    User};
use Unilend\Core\Filter\ArrayFilter;
use Unilend\Core\Service\MoneyCalculator;
use Unilend\Core\Traits\ConstantsAwareTrait;

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
 *             "role:read",
 *             "companyGroupTag:read"
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
 *                     "offer:read",
 *                     "projectParticipation:read",
 *                     "projectParticipationStatus:read",
 *                     "projectParticipationTranche:read",
 *                     "money:read",
 *                     "nullableMoney:read",
 *                     "nullablePerson:read",
 *                     "companyGroupTag:read"
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
 *                     "nullablePerson:write",
 *                     "companyGroupTag:read"
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
 *                 "permission:read",
 *                 "archivable:read",
 *                 "projectOrganizer:read",
 *                 "tranche_project:read",
 *                 "tranche:read",
 *                 "role:read",
 *                 "user:read",
 *                 "timestampable:read",
 *                 "traceableStatus:read",
 *                 "lendingRate:read",
 *                 "fee:read",
 *                 "tag:read",
 *                 "nullablePerson:read",
 *                 "nullableMoney:read",
 *                 "rangedOfferWithFee:read",
 *                 "offerWithFee:read",
 *                 "offer:read",
 *                 "companyStatus:read",
 *                 "companyGroupTag:read"
 *             }}
 *         },
 *         "project_nda": {
 *             "method": "GET",
 *             "security": "is_granted('view_nda', object)",
 *             "normalization_context": {"groups": {"project:nda:read", "file:read"}},
 *             "path": "/syndication/projects/{publicId}/nda"
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {"project:update", "projectStatus:create", "project:write", "company:write", "money:write", "nullableMoney:write", "tag:write", "nullablePerson:write"}
 *             },
 *             "normalization_context": {
 *                 "groups": {
 *                     "project:list",
 *                     "projectStatus:read",
 *                     "project:read",
 *                     "company:read",
 *                     "timestampable:read",
 *                     "projectParticipation:read",
 *                     "projectParticipationStatus:read",
 *                     "projectParticipationMember:read",
 *                     "projectParticipationTranche:read",
 *                     "money:read",
 *                     "nullableMoney:read",
 *                     "nullablePerson:read",
 *                     "rangedOfferWithFee:read",
 *                     "offerWithFee:read",
 *                     "offer:read",
 *                     "companyStatus:read",
 *                     "companyGroupTag:read"
 *                 }
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
 * @ORM\Table(name="syndication_project")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Syndication\Entity\Versioned\VersionedProject")
 */
class Project implements TraceableStatusAwareInterface
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use PublicizeIdentityTrait;

    public const OFFER_VISIBILITY_PRIVATE     = 'private';
    public const OFFER_VISIBILITY_PARTICIPANT = 'participant';
    public const OFFER_VISIBILITY_PUBLIC      = 'public';

    public const SERIALIZER_GROUP_ADMIN_READ = 'project:admin:read'; // Additional group that is available for admin (admin user or arranger)
    public const SERIALIZER_GROUP_GCA_READ = 'project:gca:read'; // Additional group that is available for gca (crédit agricole group) staff member

    public const FIELD_CURRENT_STATUS = 'currentStatus';
    public const FIELD_DESCRIPTION    = 'description';

    public const PROJECT_FILE_TYPE_DESCRIPTION = 'project_file_description';
    public const PROJECT_FILE_TYPE_NDA         = 'project_file_nda';

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
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user_submitter",  referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private User $submitterUser;

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
     * @var string|null
     *
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?string $description = null;

    /**
     * @var File|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Core\Entity\File", orphanRemoval=true)
     * @ORM\JoinColumn(name="id_description_document", unique=true)
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?File $descriptionDocument = null;

    /**
     * @var File|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Core\Entity\File")
     * @ORM\JoinColumn(name="id_nda", unique=true)
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?File $nda = null;

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
     * @Assert\Choice(callback={CAInternalRating::class, "getConstList"})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", Project::SERIALIZER_GROUP_GCA_READ})
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
     * @ORM\OneToMany(targetEntity="Unilend\Syndication\Entity\ProjectParticipation", mappedBy="project", cascade={"persist"}, orphanRemoval=true, fetch="EAGER")
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
     * @ORM\OneToMany(targetEntity="Unilend\Syndication\Entity\ProjectOrganizer", mappedBy="project", cascade={"persist"})
     */
    private Collection $organizers;

    /**
     * @var ProjectComment[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Syndication\Entity\ProjectComment", mappedBy="project")
     * @ORM\OrderBy({"added": "DESC"})
     *
     * @Groups({"project:read"})
     */
    private Collection $projectComments;

    /**
     * @var Tranche[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Syndication\Entity\Tranche", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @Assert\Valid
     *
     * @Groups({"project:read"})
     */
    private Collection $tranches;

    /**
     * @var ProjectStatus
     *
     * @ORM\OneToOne(targetEntity="Unilend\Syndication\Entity\ProjectStatus", cascade={"persist"})
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
     * @ORM\OneToMany(targetEntity="Unilend\Syndication\Entity\ProjectStatus", mappedBy="project", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
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
     * @Assert\Choice(callback={SyndicationType::class, "getConstList"})
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
     * @Assert\Choice(callback={ParticipationType::class, "getConstList"})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?string $participationType = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=80)
     *
     * @Assert\Expression("(!this.isSubParticipation() and !value) or (this.isSubParticipation() and value)")
     * @Assert\Choice(callback={RiskType::class, "getConstList"})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?string $riskType = null;

    /**
     * @var Collection|Tag[]
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Syndication\Entity\Tag", cascade={"persist"})
     * @ORM\JoinTable(name="syndication_project_tag")
     *
     * @Groups({"project:read", "project:write"})
     */
    private Collection $tags;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
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
     * @var bool|null
     *
     * @Groups({"project:read", "project:write"})
     *
     * @ORM\Column(type="boolean", nullable=true,)
     */
    private ?bool $interestExpressionEnabled;

    /**
     * @var NullableMoney
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
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
     * @Assert\Choice(callback={FundingSpecificity::class, "getConstList"})
     */
    private ?string $fundingSpecificity;

    /**
     * @var NullablePerson
     *
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullablePerson", columnPrefix="privileged_contact_")
     *
     * @Assert\Valid
     *
     * @Groups({"project:read", "project:write"})
     */
    private NullablePerson $privilegedContactPerson;

    /**
     * @var CompanyGroupTag|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\CompanyGroupTag")
     * @ORM\JoinColumn(name="id_company_group_tag")
     *
     * @Groups({"project:read", "project:write"})
     *
     * @Assert\Choice(callback="getAvailableCompanyGroupTags")
     */
    private ?CompanyGroupTag $companyGroupTag;

    /**
     * @param Staff  $addedBy
     * @param string $riskGroupName
     * @param Money  $globalFundingMoney
     *
     * @throws Exception
     */
    public function __construct(Staff $addedBy, string $riskGroupName, Money $globalFundingMoney)
    {
        $this->submitterCompany      = $addedBy->getCompany();
        $this->submitterUser         = $addedBy->getUser();
        $arrangerParticipation       = new ProjectParticipation($addedBy->getCompany(), $this, $addedBy);
        $projectParticipationMember = new ProjectParticipationMember($arrangerParticipation, $addedBy, $addedBy);
        $projectParticipationMember->addPermission(ProjectParticipationMember::PERMISSION_WRITE);
        $arrangerParticipation->addProjectParticipationMember($projectParticipationMember);
        $this->projectParticipations = new ArrayCollection([$arrangerParticipation]);

        $this->companyGroupTag       = null;
        $this->projectFiles          = new ArrayCollection();
        $this->projectComments       = new ArrayCollection();
        $this->statuses              = new ArrayCollection();
        $this->tranches              = new ArrayCollection();
        $this->tags                  = new ArrayCollection();
        $this->organizers            = new ArrayCollection([new ProjectOrganizer($addedBy->getCompany(), $this, $addedBy)]);
        $this->added                 = new DateTimeImmutable();

        $this->setCurrentStatus(new ProjectStatus($this, ProjectStatus::STATUS_DRAFT, $addedBy));
        $contact = (new NullablePerson())
            ->setFirstName($addedBy->getUser()->getFirstName())
            ->setLastName($addedBy->getUser()->getLastName())
            ->setEmail($addedBy->getUser()->getEmail())
            ->setPhone($addedBy->getUser()->getPhone())
            ->setOccupation($addedBy->getUser()->getJobFunction());
        $this->setPrivilegedContactPerson($contact);

        $this->offerVisibility    = static::OFFER_VISIBILITY_PRIVATE;
        $this->riskGroupName      = $riskGroupName;
        $this->globalFundingMoney = $globalFundingMoney;

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
     * @return User
     */
    public function getSubmitterUser(): User
    {
        return $this->submitterUser;
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
     * @param string|null $description
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
    public function setDescriptionDocument(?File $file): Project
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
    public function setNda(?File $file): Project
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
        $this->currentStatus->setProject($this);

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
     *
     * @return $this
     */
    public function setInternalRatingScore(?string $internalRatingScore): Project
    {
        $this->internalRatingScore = $internalRatingScore;

        return $this;
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
     * @return Company
     *
     * @Groups({"project:read"})
     *
     * @MaxDepth(1)
     */
    public function getArranger(): Company
    {
        return $this->getSubmitterCompany();
    }

    /**
     * @return ProjectParticipation
     */
    public function getArrangerProjectParticipation(): ProjectParticipation
    {
        $filtered = $this->projectParticipations->filter(function (ProjectParticipation $projectParticipation) {
            return $projectParticipation->getParticipant() === $this->getArranger();
        });

        if (1 < $filtered->count()) {
            throw new DomainException(sprintf('There are more than one participations for arranger (id: %d) on project (id: %s)', $this->getArranger()->getId(), $this->getId()));
        }

        if (0 === $filtered->count()) {
            throw new DomainException(sprintf('There is no participation for arranger (id: %d) on project (id: %s)', $this->getArranger()->getId(), $this->getId()));
        }

        return $filtered->first();
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
     * @return bool
     */
    public function isPrimary(): bool
    {
        return SyndicationType::PRIMARY === $this->syndicationType;
    }

    /**
     * @return bool
     */
    public function isSecondary(): bool
    {
        return SyndicationType::SECONDARY === $this->syndicationType;
    }

    /**
     * @return bool
     */
    public function isDirect(): bool
    {
        return ParticipationType::DIRECT === $this->participationType;
    }

    /**
     * @return bool
     */
    public function isSubParticipation(): bool
    {
        return ParticipationType::SUB_PARTICIPATION === $this->participationType;
    }

    /**
     * @return bool
     */
    public function isRisk(): bool
    {
        return $this->isSubParticipation() && (RiskType::RISK === $this->riskType);
    }

    /**
     * @return bool
     */
    public function isRiskAndTreasury(): bool
    {
        return $this->isSubParticipation() && (RiskType::RISK_TREASURY === $this->riskType);
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
     * @param ProjectParticipation $projectParticipation
     *
     * @return Project
     */
    public function addProjectParticipation(ProjectParticipation $projectParticipation): Project
    {
        if (false === $this->projectParticipations->contains($projectParticipation)) {
            $this->projectParticipations->add($projectParticipation);
        }

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
        $totalAllocationMoney = $this->getTotalAllocationMoney();

        return 1 === MoneyCalculator::compare($totalAllocationMoney, $this->getTranchesTotalMoney());
    }

    /**
     * @return MoneyInterface
     */
    public function getTotalAllocationMoney(): MoneyInterface
    {
        $totalAllocationMoney = new NullableMoney();

        foreach ($this->getTranches() as $tranche) {
            $totalAllocationMoney = MoneyCalculator::add($totalAllocationMoney, $tranche->getTotalAllocationMoney());
        }

        return $totalAllocationMoney;
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
     * @return bool|null
     */
    public function isInterestExpressionEnabled(): ?bool
    {
        return $this->interestExpressionEnabled;
    }

    /**
     * @param bool|null $interestExpressionEnabled
     *
     * @return Project
     */
    public function setInterestExpressionEnabled(?bool $interestExpressionEnabled): Project
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
    public function isDraft(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_DRAFT);
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
     * @Groups({"project:read"})
     *
     * @return bool
     */
    public function isMandatoryInformationComplete(): bool
    {
        return $this->syndicationType
            && ($this->description || $this->descriptionDocument)
            && $this->getTranches()->count() > 0
            && $this->getArrangerProjectParticipation()->getInvitationRequest()->isValid()
            && $this->getPrivilegedContactPerson()->isValid()
            && $this->allocationDeadline
            && $this->participantReplyDeadline
            // ensure interestExpressionDeadline is present only if interest expression is enabled
            && is_bool($this->interestExpressionEnabled)
            && false === $this->interestExpressionEnabled xor null !== $this->interestExpressionDeadline
            && $this->participationType
            ;
    }

    /**
     * @return bool
     */
    public function hasEditableStatus(): bool
    {
        return false === \in_array($this->getCurrentStatus()->getStatus(), ProjectStatus::NON_EDITABLE_STATUSES, true);
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateParticipantReplyDeadline(ExecutionContextInterface $context)
    {
        if ($this->hasCompletedStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION) && null === $this->getParticipantReplyDeadline()) {
            $context->buildViolation('Syndication.Project.participantReplyDeadline.required')
                ->atPath('participantReplyDeadline')
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateProjectParticipations(ExecutionContextInterface $context)
    {
        foreach ($this->projectParticipations as $index => $projectParticipation) {
            if ($projectParticipation->getProject() !== $this) {
                $context->buildViolation('Syndication.Project.projectParticipations.incorrectProject')
                    ->atPath("projectParticipation[$index]")
                    ->addViolation();
            }
        }
    }

    /**
     * @return CompanyGroupTag|null
     */
    public function getCompanyGroupTag(): ?CompanyGroupTag
    {
        return $this->companyGroupTag;
    }

    /**
     * @param CompanyGroupTag|null $companyGroupTag
     *
     * @return Project
     */
    public function setCompanyGroupTag(?CompanyGroupTag $companyGroupTag): Project
    {
        $this->companyGroupTag = $companyGroupTag;

        return $this;
    }

    /**
     * @return CompanyGroupTag[]|array
     */
    public function getAvailableCompanyGroupTags(): array
    {
        return $this->getArranger()->getCompanyGroupTags();
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
