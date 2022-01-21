<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyGroupTag;
use KLS\Core\Entity\Constant\CAInternalRating;
use KLS\Core\Entity\Constant\FundingSpecificity;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Embeddable\NullablePerson;
use KLS\Core\Entity\File;
use KLS\Core\Entity\Interfaces\FileTypesAwareInterface;
use KLS\Core\Entity\Interfaces\MoneyInterface;
use KLS\Core\Entity\Interfaces\StatusInterface;
use KLS\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Entity\User;
use KLS\Core\Filter\ArrayFilter;
use KLS\Core\Service\MoneyCalculator;
use KLS\Core\Traits\ConstantsAwareTrait;
use KLS\Syndication\Arrangement\Entity\Embeddable\OfferWithFee;
use KLS\Syndication\Arrangement\Entity\Embeddable\RangedOfferWithFee;
use KLS\Syndication\Common\Constant\Modality\ParticipationType;
use KLS\Syndication\Common\Constant\Modality\RiskType;
use KLS\Syndication\Common\Constant\Modality\SyndicationType;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
 *             "companyGroupTag:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "project:write",
 *             "company:write",
 *             "money:write",
 *             "nullablePerson:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     collectionOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "project:read",
 *                     "company:read",
 *                     "offer:read",
 *                     "projectParticipation:read",
 *                     "projectParticipationStatus:read",
 *                     "projectParticipationTranche:read",
 *                     "money:read",
 *                     "nullableMoney:read",
 *                     "nullablePerson:read",
 *                     "companyGroupTag:read",
 *                 },
 *                 "openapi_definition_name": "collection-get-read",
 *             },
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
 *                     "nullablePerson:write",
 *                     "companyGroupTag:read",
 *                 },
 *                 "openapi_definition_name": "collection-post-write",
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {
 *                 "groups": {
 *                     "project:read",
 *                     "company:read",
 *                     "projectParticipation:read",
 *                     "projectParticipationTranche:read",
 *                     "projectParticipationStatus:read",
 *                     "money:read",
 *                     "file:read",
 *                     "fileVersion:read",
 *                     "projectStatus:read",
 *                     "projectParticipationMember:read",
 *                     "permission:read",
 *                     "archivable:read",
 *                     "projectOrganizer:read",
 *                     "tranche_project:read",
 *                     "tranche:read",
 *                     "role:read",
 *                     "user:read",
 *                     "timestampable:read",
 *                     "traceableStatus:read",
 *                     "lendingRate:read",
 *                     "fee:read",
 *                     "nullablePerson:read",
 *                     "nullableMoney:read",
 *                     "rangedOfferWithFee:read",
 *                     "offerWithFee:read",
 *                     "offer:read",
 *                     "companyStatus:read",
 *                     "companyGroupTag:read",
 *                 },
 *                 "openapi_definition_name": "item-get-read",
 *             },
 *         },
 *         "project_nda": {
 *             "method": "GET",
 *             "security": "is_granted('view_nda', object)",
 *             "normalization_context": {"groups": {"project:nda:read", "file:read"}},
 *             "path": "/syndication/projects/{publicId}/nda",
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "project:update",
 *                     "projectStatus:create",
 *                     "project:write",
 *                     "company:write",
 *                     "money:write",
 *                     "nullableMoney:write",
 *                     "nullablePerson:write",
 *                 },
 *                 "openapi_definition_name": "item-patch-write",
 *             },
 *             "normalization_context": {
 *                 "groups": {
 *                     "project:read",
 *                     "company:read",
 *                     "projectParticipation:read",
 *                     "projectParticipationTranche:read",
 *                     "projectParticipationStatus:read",
 *                     "money:read",
 *                     "file:read",
 *                     "fileVersion:read",
 *                     "projectStatus:read",
 *                     "projectParticipationMember:read",
 *                     "permission:read",
 *                     "archivable:read",
 *                     "projectOrganizer:read",
 *                     "tranche_project:read",
 *                     "tranche:read",
 *                     "role:read",
 *                     "user:read",
 *                     "timestampable:read",
 *                     "traceableStatus:read",
 *                     "lendingRate:read",
 *                     "fee:read",
 *                     "nullablePerson:read",
 *                     "nullableMoney:read",
 *                     "rangedOfferWithFee:read",
 *                     "offerWithFee:read",
 *                     "offer:read",
 *                     "companyStatus:read",
 *                     "companyGroupTag:read",
 *                 },
 *                 "openapi_definition_name": "item-patch-read",
 *             },
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         },
 *     },
 * )
 *
 * @ApiFilter(NumericFilter::class, properties={"currentStatus.status"})
 * @ApiFilter(ArrayFilter::class, properties={"organizers.roles"})
 * @ApiFilter(SearchFilter::class, properties={"submitterCompany.publicId", "organizers.company.publicId"})
 * @ApiFilter(BooleanFilter::class, properties={"agencyImported"})
 *
 * @ORM\Table(name="syndication_project")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="KLS\Syndication\Arrangement\Entity\Versioned\VersionedProject")
 */
class Project implements TraceableStatusAwareInterface, FileTypesAwareInterface
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use PublicizeIdentityTrait;

    public const OFFER_VISIBILITY_PRIVATE     = 'private';
    public const OFFER_VISIBILITY_PARTICIPANT = 'participant';
    public const OFFER_VISIBILITY_PUBLIC      = 'public';

    // Additional group that is available for admin (admin user or arranger)
    public const SERIALIZER_GROUP_ADMIN_READ = 'project:admin:read';
    // Additional group that is available for gca (crédit agricole group) staff member
    public const SERIALIZER_GROUP_GCA_READ = 'project:gca:read';

    public const FIELD_CURRENT_STATUS = 'currentStatus';
    public const FIELD_DESCRIPTION    = 'description';

    public const PROJECT_FILE_TYPE_DESCRIPTION = 'project_file_description';
    public const PROJECT_FILE_TYPE_NDA         = 'project_file_nda';

    /**
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
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
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
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user_submitter",  referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private User $submitterUser;

    /**
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
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?string $description = null;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\File", orphanRemoval=true)
     * @ORM\JoinColumn(name="id_term_sheet", unique=true)
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?File $termSheet = null;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Core\Entity\File")
     * @ORM\JoinColumn(name="id_nda", unique=true)
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?File $nda = null;

    /**
     * en front (barre de progression projet) : Signature.
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
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:write", "project:read"})
     */
    private ?DateTimeImmutable $contractualizationDeadline;

    /**
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
     * @ORM\OneToMany(
     *     targetEntity="KLS\Syndication\Arrangement\Entity\ProjectParticipation",
     *     mappedBy="project",
     *     cascade={"persist"},
     *     orphanRemoval=true,
     *     fetch="EAGER"
     * )
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
     * @ORM\OneToMany(
     *     targetEntity="KLS\Syndication\Arrangement\Entity\ProjectOrganizer",
     *     mappedBy="project",
     *     cascade={"persist"}
     * )
     */
    private Collection $organizers;

    /**
     * @var ProjectComment[]|Collection
     *
     * @ORM\OneToMany(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectComment", mappedBy="project")
     * @ORM\OrderBy({"added": "DESC"})
     *
     * @Groups({"project:read"})
     */
    private Collection $projectComments;

    /**
     * @var Tranche[]|Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\Syndication\Arrangement\Entity\Tranche",
     *     mappedBy="project",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     *
     * @Assert\Valid
     *
     * @Groups({"project:read"})
     */
    private Collection $tranches;

    /**
     * @ORM\OneToOne(targetEntity="KLS\Syndication\Arrangement\Entity\ProjectStatus", cascade={"persist"})
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
     * @ORM\OneToMany(
     *     targetEntity="KLS\Syndication\Arrangement\Entity\ProjectStatus",
     *     mappedBy="project",
     *     orphanRemoval=true,
     *     cascade={"persist"},
     *     fetch="EAGER"
     * )
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"project:read"})
     */
    private Collection $statuses;

    /**
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
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\Money")
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
     * @Groups({"project:read", "project:write"})
     *
     * @ORM\Column(type="boolean", nullable=true, )
     */
    private ?bool $interestExpressionEnabled;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"project:admin:read", "project:write"})
     */
    private NullableMoney $arrangementCommissionMoney;

    /**
     * @Groups({"project:read", "project:write"})
     *
     * @ORM\Column(type="string", nullable=true, length=30)
     *
     * @Assert\Choice(callback={FundingSpecificity::class, "getConstList"})
     */
    private ?string $fundingSpecificity;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullablePerson", columnPrefix="privileged_contact_")
     *
     * @Assert\Valid
     *
     * @Groups({"project:read", "project:write"})
     */
    private NullablePerson $privilegedContactPerson;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\CompanyGroupTag")
     * @ORM\JoinColumn(name="id_company_group_tag")
     *
     * @Groups({"project:read", "project:write"})
     *
     * @Assert\Choice(callback="getAvailableCompanyGroupTags")
     */
    private ?CompanyGroupTag $companyGroupTag;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $agencyImported;

    /**
     * @throws Exception
     */
    public function __construct(Staff $addedBy, string $riskGroupName, Money $globalFundingMoney)
    {
        $this->submitterCompany     = $addedBy->getCompany();
        $this->submitterUser        = $addedBy->getUser();
        $arrangerParticipation      = new ProjectParticipation($addedBy->getCompany(), $this, $addedBy);
        $projectParticipationMember = new ProjectParticipationMember($arrangerParticipation, $addedBy, $addedBy);
        $projectParticipationMember->addPermission(ProjectParticipationMember::PERMISSION_WRITE);
        $arrangerParticipation->addProjectParticipationMember($projectParticipationMember);
        $this->projectParticipations = new ArrayCollection([$arrangerParticipation]);

        $this->companyGroupTag = null;
        $this->projectFiles    = new ArrayCollection();
        $this->projectComments = new ArrayCollection();
        $this->statuses        = new ArrayCollection();
        $this->tranches        = new ArrayCollection();
        $this->organizers      = new ArrayCollection([new ProjectOrganizer($addedBy->getCompany(), $this, $addedBy)]);
        $this->added           = new DateTimeImmutable();
        $this->agencyImported  = false;

        $this->setCurrentStatus(new ProjectStatus($this, ProjectStatus::STATUS_DRAFT, $addedBy));
        $contact = (new NullablePerson())
            ->setFirstName($addedBy->getUser()->getFirstName())
            ->setLastName($addedBy->getUser()->getLastName())
            ->setEmail($addedBy->getUser()->getEmail())
            ->setPhone($addedBy->getUser()->getPhone())
            ->setOccupation($addedBy->getUser()->getJobFunction())
        ;
        $this->setPrivilegedContactPerson($contact);

        $this->offerVisibility    = static::OFFER_VISIBILITY_PRIVATE;
        $this->riskGroupName      = $riskGroupName;
        $this->globalFundingMoney = $globalFundingMoney;

        $this->arrangementCommissionMoney = new NullableMoney();
    }

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

    public function getSubmitterCompany(): Company
    {
        return $this->submitterCompany;
    }

    public function getSubmitterUser(): User
    {
        return $this->submitterUser;
    }

    /**
     * @param string $title
     */
    public function setTitle($title): Project
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(?string $description): Project
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setTermSheet(?File $file): Project
    {
        $this->termSheet = $file;

        return $this;
    }

    public function getTermSheet(): ?File
    {
        return $this->termSheet;
    }

    public function setNda(?File $file): Project
    {
        $this->nda = $file;

        return $this;
    }

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

    public function getSigningDeadline(): ?DateTimeImmutable
    {
        return $this->signingDeadline;
    }

    public function setSigningDeadline(?DateTimeImmutable $signingDeadline): Project
    {
        $this->signingDeadline = $signingDeadline;

        return $this;
    }

    public function getAllocationDeadline(): ?DateTimeImmutable
    {
        return $this->allocationDeadline;
    }

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

    public function setParticipantReplyDeadline(?DateTimeImmutable $participantReplyDeadline): Project
    {
        $this->participantReplyDeadline = $participantReplyDeadline;

        return $this;
    }

    public function getInternalRatingScore(): ?string
    {
        return $this->internalRatingScore;
    }

    /**
     * @return $this
     */
    public function setInternalRatingScore(?string $internalRatingScore): Project
    {
        $this->internalRatingScore = $internalRatingScore;

        return $this;
    }

    public function getOfferVisibility(): string
    {
        return $this->offerVisibility;
    }

    public function setOfferVisibility(string $offerVisibility): Project
    {
        $this->offerVisibility = $offerVisibility;

        return $this;
    }

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

    public function getProjectParticipationByCompany(Company $company): ?ProjectParticipation
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq('company', $company));

        // A company can only have one Participation on a project.
        return $this->projectParticipations->matching($criteria)->first() ?: null;
    }

    public function removeProjectParticipation(ProjectParticipation $projectParticipation): Project
    {
        $this->projectParticipations->removeElement($projectParticipation);

        return $this;
    }

    /**
     * @Groups({"project:read"})
     *
     * @MaxDepth(1)
     */
    public function getArranger(): Company
    {
        return $this->getSubmitterCompany();
    }

    public function getArrangerProjectParticipation(): ProjectParticipation
    {
        $filtered = $this->projectParticipations->filter(function (ProjectParticipation $projectParticipation) {
            return $projectParticipation->getParticipant() === $this->getArranger();
        });

        if (1 < $filtered->count()) {
            throw new DomainException(
                \sprintf(
                    'There are more than one participations for arranger (id: %d) on project (id: %s)',
                    $this->getArranger()->getId(),
                    $this->getId()
                )
            );
        }

        if (0 === $filtered->count()) {
            throw new DomainException(
                \sprintf(
                    'There is no participation for arranger (id: %d) on project (id: %s)',
                    $this->getArranger()->getId(),
                    $this->getId()
                )
            );
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
     */
    public function getRun(): ?ProjectOrganizer
    {
        return $this->getUniqueOrganizer(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_RUN);
    }

    /**
     * @throws Exception
     */
    public function getAgent(): ?ProjectOrganizer
    {
        return $this->getUniqueOrganizer(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_AGENT);
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

    public function addTranche(Tranche $tranche): Project
    {
        $tranche->setProject($this);

        if (false === $this->tranches->contains($tranche)) {
            $this->tranches->add($tranche);
        }

        return $this;
    }

    public function removeTranche(Tranche $tranche): Project
    {
        $this->tranches->removeElement($tranche);

        return $this;
    }

    public function isPrimary(): bool
    {
        return SyndicationType::PRIMARY === $this->syndicationType;
    }

    public function isSecondary(): bool
    {
        return SyndicationType::SECONDARY === $this->syndicationType;
    }

    public function isDirect(): bool
    {
        return ParticipationType::DIRECT === $this->participationType;
    }

    public function isSubParticipation(): bool
    {
        return ParticipationType::SUB_PARTICIPATION === $this->participationType;
    }

    public function isRisk(): bool
    {
        return $this->isSubParticipation() && (RiskType::RISK === $this->riskType);
    }

    public function isRiskAndTreasury(): bool
    {
        return $this->isSubParticipation() && (RiskType::RISK_TREASURY === $this->riskType);
    }

    public function getSyndicationType(): ?string
    {
        return $this->syndicationType;
    }

    public function setSyndicationType(?string $syndicationType): Project
    {
        $this->syndicationType = $syndicationType;

        return $this;
    }

    public function getParticipationType(): ?string
    {
        return $this->participationType;
    }

    public function setParticipationType(?string $participationType): Project
    {
        $this->participationType = $participationType;

        if (false === $this->isSubParticipation()) {
            $this->riskType = null;
        }

        return $this;
    }

    public function getRiskType(): ?string
    {
        return $this->riskType;
    }

    public function setRiskType(?string $riskType): Project
    {
        $this->riskType = $riskType;

        return $this;
    }

    public function addProjectParticipation(ProjectParticipation $projectParticipation): Project
    {
        if (false === $this->projectParticipations->exists($projectParticipation->getEquivalenceChecker())) {
            $this->projectParticipations->add($projectParticipation);
        }

        return $this;
    }

    /**
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

    public function isOversubscribed(): bool
    {
        $totalAllocationMoney = $this->getTotalAllocationMoney();

        return 1 === MoneyCalculator::compare($totalAllocationMoney, $this->getTranchesTotalMoney());
    }

    public function getTotalAllocationMoney(): MoneyInterface
    {
        $totalAllocationMoney = new NullableMoney();

        foreach ($this->getTranches() as $tranche) {
            $totalAllocationMoney = MoneyCalculator::add($totalAllocationMoney, $tranche->getTotalAllocationMoney());
        }

        return $totalAllocationMoney;
    }

    public function setGlobalFundingMoney(Money $globalFundingMoney): Project
    {
        $this->globalFundingMoney = $globalFundingMoney;

        return $this;
    }

    public function getGlobalFundingMoney(): Money
    {
        return $this->globalFundingMoney;
    }

    /**
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

    public function isAgencyImported(): bool
    {
        return $this->agencyImported;
    }

    public function setAgencyImported(bool $agencyImported): Project
    {
        $this->agencyImported = $agencyImported;

        return $this;
    }

    public static function getProjectFileTypes(): array
    {
        return self::getConstants('PROJECT_FILE_TYPE_');
    }

    public static function getFileTypes(): array
    {
        return \array_merge(static::getProjectFileTypes(), ProjectFile::getProjectFileTypes());
    }

    public function getInterestExpressionDeadline(): ?DateTimeImmutable
    {
        return $this->interestExpressionDeadline;
    }

    public function setInterestExpressionDeadline(?DateTimeImmutable $interestExpressionDeadline): Project
    {
        $this->interestExpressionDeadline = $interestExpressionDeadline;

        return $this;
    }

    public function getContractualizationDeadline(): ?DateTimeImmutable
    {
        return $this->contractualizationDeadline;
    }

    public function setContractualizationDeadline(?DateTimeImmutable $contractualizationDeadline): Project
    {
        $this->contractualizationDeadline = $contractualizationDeadline;

        return $this;
    }

    public function isInterestExpressionEnabled(): ?bool
    {
        return $this->interestExpressionEnabled;
    }

    /**
     * @throws Exception
     */
    public function setInterestExpressionEnabled(?bool $interestExpressionEnabled): Project
    {
        if (
            null === $this->getCurrentStatus()
            || ProjectStatus::STATUS_DRAFT === $this->getCurrentStatus()->getStatus()
        ) {
            $this->interestExpressionEnabled = $interestExpressionEnabled;

            foreach ($this->projectParticipations as $participation) {
                if ($this->interestExpressionEnabled) {
                    $invitationRequest = $participation->getInvitationRequest();
                    $participation->setInterestRequest(
                        new RangedOfferWithFee($invitationRequest->getMoney(), $invitationRequest->getFeeRate())
                    );
                    $participation->setInvitationRequest(new OfferWithFee());
                } else {
                    $interestRequest = $participation->getInterestRequest();
                    $participation->setInvitationRequest(
                        new OfferWithFee($interestRequest->getMoney(), $interestRequest->getFeeRate())
                    );
                    // Must reset interestExpression deadline as this date
                    // should not be filled if there is no interestRequest phase
                    $this->interestExpressionDeadline = null;
                    $participation->setInterestRequest(new RangedOfferWithFee());
                }
            }
        }

        return $this;
    }

    public function getArrangementCommissionMoney(): ?NullableMoney
    {
        return $this->arrangementCommissionMoney->isValid() ? $this->arrangementCommissionMoney : null;
    }

    public function setArrangementCommissionMoney(NullableMoney $arrangementCommissionMoney): Project
    {
        $this->arrangementCommissionMoney = $arrangementCommissionMoney;

        return $this;
    }

    public function getFundingSpecificity(): ?string
    {
        return $this->fundingSpecificity;
    }

    public function setFundingSpecificity(?string $fundingSpecificity): Project
    {
        $this->fundingSpecificity = $fundingSpecificity;

        return $this;
    }

    public function getPrivilegedContactPerson(): NullablePerson
    {
        return $this->privilegedContactPerson;
    }

    public function setPrivilegedContactPerson(NullablePerson $privilegedContactPerson): Project
    {
        $this->privilegedContactPerson = $privilegedContactPerson;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->hasCompletedStatus(ProjectStatus::STATUS_DRAFT);
    }

    public function isDraft(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_DRAFT);
    }

    public function isInterestCollected(): bool
    {
        return $this->hasCompletedStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION);
    }

    public function isInInterestCollectionStep(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION);
    }

    public function isInOfferNegotiationStep(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_PARTICIPANT_REPLY);
    }

    public function isInAllocationStep(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_ALLOCATION);
    }

    /**
     * Used in an expression constraints.
     */
    public function isInContractNegotiationStep(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_CONTRACTUALISATION);
    }

    public function isFinished(): bool
    {
        return $this->hasCurrentStatus(ProjectStatus::STATUS_SYNDICATION_FINISHED);
    }

    public function hasCurrentStatus(int $testedStatus): bool
    {
        return $testedStatus === $this->getCurrentStatus()->getStatus();
    }

    public function hasCompletedStatus(int $testedStatus): bool
    {
        return $this->getCurrentStatus()->getStatus() > $testedStatus;
    }

    /**
     * @Groups({"project:read"})
     */
    public function isMandatoryInformationComplete(): bool
    {
        return $this->syndicationType
            && ($this->description || $this->termSheet)
            && $this->getTranches()->count() > 0
            && $this->getArrangerProjectParticipation()->getInvitationRequest()->isValid()
            && $this->getPrivilegedContactPerson()->isValid()
            && $this->allocationDeadline
            && $this->participantReplyDeadline
            // ensure interestExpressionDeadline is present only if interest expression is enabled
            && \is_bool($this->interestExpressionEnabled)
            && false === $this->interestExpressionEnabled xor null !== $this->interestExpressionDeadline
            && $this->participationType
            ;
    }

    public function hasEditableStatus(): bool
    {
        return false === \in_array($this->getCurrentStatus()->getStatus(), ProjectStatus::NON_EDITABLE_STATUSES, true);
    }

    /**
     * @Assert\Callback
     */
    public function validateParticipantReplyDeadline(ExecutionContextInterface $context)
    {
        if (
            $this->hasCompletedStatus(ProjectStatus::STATUS_INTEREST_EXPRESSION)
            && null === $this->getParticipantReplyDeadline()
        ) {
            $context->buildViolation('Syndication.Project.participantReplyDeadline.required')
                ->atPath('participantReplyDeadline')
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateProjectParticipations(ExecutionContextInterface $context)
    {
        foreach ($this->projectParticipations as $index => $projectParticipation) {
            if ($projectParticipation->getProject() !== $this) {
                $context->buildViolation('Syndication.Project.projectParticipations.incorrectProject')
                    ->atPath("projectParticipation[{$index}]")
                    ->addViolation()
                ;
            }
        }
    }

    public function getCompanyGroupTag(): ?CompanyGroupTag
    {
        return $this->companyGroupTag;
    }

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
     * @return ProjectOrganizer[]|Collection
     */
    private function getOrganizersByRole(string $role): Collection
    {
        $organizers = new ArrayCollection();

        // Ugly foreach on the Organizer (hopefully we don't have many organisers on a project),
        // as the Criteria doesn't support the json syntax.
        foreach ($this->getOrganizers() as $organizer) {
            if ($organizer->hasRole($role)) {
                $organizers->add($organizer);
            }
        }

        return $organizers;
    }

    private function getUniqueOrganizer(string $role): ?ProjectOrganizer
    {
        if (false === ProjectOrganizer::isUniqueRole($role)) {
            throw new RuntimeException(
                \sprintf(
                    'Role "%s" is not unique. Cannot get project Participation corresponding to the role.',
                    $role
                )
            );
        }

        return $this->getOrganizersByRole($role)->first() ?: null;
    }
}
