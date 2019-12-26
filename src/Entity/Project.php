<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiProperty, ApiResource, ApiSubresource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
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
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Traits\{TimestampableTrait, TraceableStatusTrait};
use Unilend\Filter\ArrayFilter;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {"normalization_context": {"groups": {"project:list", "marketSegment:read", "projectParticipation:read"}}},
 *         "post": {"denormalization_context": {"groups": {"project:create"}}}
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *             "normalization_context": {"groups": {"project:view", "tranche_project:view", "attachment:read", "projectParticipation:read"}}
 *         },
 *         "project_confidentiality": {
 *             "method": "GET",
 *             "security": "is_granted('view_confidentiality_doc', object)",
 *             "normalization_context": {"groups": {"project:confidentiality:view", "attachment:read"}},
 *             "path": "/projects/{id}/confidentiality"
 *         },
 *         "patch": {"security_post_denormalize": "is_granted('edit', previous_object)", "denormalization_context": {"groups": {"project:update"}}}
 *     }
 * )
 *
 * @ApiFilter(NumericFilter::class, properties={"currentStatus.status"})
 * @ApiFilter(SearchFilter::class, properties={"organizers.company.id"})
 * @ApiFilter(ArrayFilter::class, properties={"organizers.roles"})
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
     * @Groups({"project:list", "project:view", "projectParticipation:list"})
     */
    private $hash;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_borrower_company", referencedColumnName="id", nullable=false)
     * })
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update", "project:list", "project:view", "projectParticipation:list"})
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @MaxDepth(1)
     */
    private $borrowerCompany;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company_submitter", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"project:list"})
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
     *     @ORM\JoinColumn(name="id_client_submitter",  referencedColumnName="id_client", nullable=false)
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
     * @Groups({"project:create", "project:update", "project:list", "project:view", "projectParticipation:list"})
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
     * @Groups({"project:list", "project:create", "project:update", "project:view", "projectParticipation:list"})
     */
    private $marketSegment;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update", "project:view"})
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update", "project:view"})
     */
    private $confidential = false;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update", "project:view", "project:confidentiality:view"})
     */
    private $confidentialityDisclaimer;

    /**
     * Date limite de réponse de la contrepartie (borrowerCompany).
     *
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update", "project:view", "projectParticipation:list"})
     */
    private $replyDeadline;

    /**
     * Date de la fin de la syndication.
     *
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update", "project:view", "projectParticipation:list"})
     */
    private $expectedClosingDate;

    /**
     * Date de fin de collection des offres participant.
     *
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update", "project:list", "project:view", "projectParticipation:list"})
     */
    private $lenderConsultationClosingDate;

    /**
     * @var string|null
     *
     * @ORM\Column(length=8, nullable=true)
     *
     * @Assert\Choice(callback="getInternalRatingScores")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update", "project:view"})
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
     * @Groups({"project:create", "project:update", "project:view"})
     */
    private $offerVisibility;

    /**
     * @var Attachment[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Attachment", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @ApiSubresource
     *
     * @Groups({"project:view"})
     */
    private $attachments;

    /**
     * @var ProjectParticipation[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipation", mappedBy="project", cascade={"persist"}, orphanRemoval=true, fetch="EAGER")
     */
    private $projectParticipations;

    /**
     * @var ProjectOrganizer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectOrganizer", mappedBy="project")
     */
    private $organizers;

    /**
     * @var ProjectComment[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectComment", mappedBy="project")
     * @ORM\OrderBy({"added": "DESC"})
     *
     * @Groups({"project:view"})
     */
    private $comments;

    /**
     * @var Tranche[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Tranche", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @Assert\Valid
     *
     * @Groups({"project:create", "project:view"})
     */
    private $tranches;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=true, length=320)
     */
    private $image;

    /**
     * @var ProjectStatus
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectStatus")
     * @ORM\JoinColumn(name="id_current_status", unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"project:view", "projectParticipation:list", "project:update"})
     */
    private $currentStatus;

    /**
     * @var ArrayCollection|ProjectStatus
     *
     * @Assert\Count(min="1")
     * @Assert\Valid
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectStatus", mappedBy="project", orphanRemoval=true, cascade={"persist"})
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
     * @Groups({"project:create", "project:update", "project:view", "projectParticipation:list"})
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
     * @Groups({"project:create", "project:update", "project:view", "projectParticipation:list"})
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
     * @Groups({"project:create", "project:update", "project:view", "projectParticipation:list"})
     */
    private $riskType;

    /**
     * @var Collection|Tag[]
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Entity\Tag", cascade={"persist"})
     *
     * @Groups({"project:view", "project:update"})
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
     * @Groups({"project:create", "project:view", "project:update", "projectParticipation:list"})
     */
    private $globalFundingMoney;

    /**
     * @param Clients   $submitter
     * @param Companies $borrowerCompany
     * @param Money     $globalFundingMoney
     *
     * @throws Exception
     */
    public function __construct(Clients $submitter, Companies $borrowerCompany, Money $globalFundingMoney)
    {
        $this->attachments           = new ArrayCollection();
        $this->projectParticipations = new ArrayCollection();
        $this->comments              = new ArrayCollection();
        $this->statuses              = new ArrayCollection();
        $this->tranches              = new ArrayCollection();
        $this->tags                  = new ArrayCollection();
        $this->organizers            = new ArrayCollection();
        $this->added                 = new DateTimeImmutable();

        $this->setCurrentStatus(new ProjectStatus($this, ProjectStatus::STATUS_REQUESTED));

        $this->submitterClient  = $submitter;
        $this->submitterCompany = $submitter->getCompany();

        $this->syndicationType   = static::PROJECT_SYNDICATION_TYPE_PRIMARY;
        $this->participationType = static::PROJECT_PARTICIPATION_TYPE_DIRECT;
        $this->offerVisibility   = static::OFFER_VISIBILITY_PRIVATE;

        $this->borrowerCompany    = $borrowerCompany;
        $this->globalFundingMoney = $globalFundingMoney;

        if (null === $this->hash) {
            try {
                $this->hash = (string) (Uuid::uuid4());
            } catch (Throwable $e) {
                $this->hash = md5(uniqid('', false));
            }
        }

        $arranger = new ProjectOrganizer($submitter->getCompany(), $this, $submitter, [ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER]);
        $this->organizers->add($arranger);
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
     * @param Companies $company
     *
     * @return Project
     */
    public function setBorrowerCompany(Companies $company): Project
    {
        $this->borrowerCompany = $company;

        return $this;
    }

    /**
     * @return Companies
     */
    public function getBorrowerCompany(): Companies
    {
        return $this->borrowerCompany;
    }

    /**
     * @return Companies|null
     */
    public function getSubmitterCompany(): ?Companies
    {
        return $this->submitterCompany;
    }

    /**
     * @return Clients|null
     */
    public function getSubmitterClient(): ?Clients
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
     * @return string|null
     */
    public function getTitle(): ?string
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
     * @return string|null
     */
    public function getConfidentialityDisclaimer(): ?string
    {
        return $this->confidentialityDisclaimer;
    }

    /**
     * @param string|null $confidentialityDisclaimer
     *
     * @return Project
     */
    public function setConfidentialityDisclaimer(?string $confidentialityDisclaimer): Project
    {
        $this->confidentialityDisclaimer = $confidentialityDisclaimer;

        return $this;
    }

    /**
     * @return Attachment|null
     *
     * @Groups({"project:confidentiality:view", "project:view"})
     */
    public function getConfidentialityDisclaimerDocument(): ?Attachment
    {
        return $this->attachments->filter(
            static function (Attachment $attachment) {
                return Attachment::TYPE_PROJECT_CONFIDENTIALITY_DISCLAIMER === $attachment->getType();
            }
        )->first() ?: null;
    }

    /**
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
     * @return MarketSegment|null
     */
    public function getMarketSegment(): ?MarketSegment
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
    public function getReplyDeadline(): ?DateTimeImmutable
    {
        return $this->replyDeadline;
    }

    /**
     * @param DateTimeImmutable|null $replyDeadline
     *
     * @return Project
     */
    public function setReplyDeadline(?DateTimeImmutable $replyDeadline): Project
    {
        $this->replyDeadline = $replyDeadline;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getExpectedClosingDate(): ?DateTimeImmutable
    {
        return $this->expectedClosingDate;
    }

    /**
     * @param DateTimeImmutable|null $expectedClosingDate
     *
     * @return Project
     */
    public function setExpectedClosingDate(?DateTimeImmutable $expectedClosingDate): Project
    {
        $this->expectedClosingDate = $expectedClosingDate;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getLenderConsultationClosingDate(): ?DateTimeImmutable
    {
        return $this->lenderConsultationClosingDate;
    }

    /**
     * @param DateTimeImmutable|null $lenderConsultationClosingDate
     *
     * @return Project
     */
    public function setLenderConsultationClosingDate(?DateTimeImmutable $lenderConsultationClosingDate): Project
    {
        $this->lenderConsultationClosingDate = $lenderConsultationClosingDate;

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
     * @return ProjectParticipation[]|Collection
     */
    public function getProjectParticipations(): Collection
    {
        return $this->projectParticipations;
    }

    /**
     * @param Companies $companies
     *
     * @return ProjectParticipation|null
     */
    public function getProjectParticipationByCompany(Companies $companies): ?ProjectParticipation
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq('company', $companies));

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
     * @throws Exception
     *
     * @return ProjectOrganizer|null
     *
     * @Groups({"project:view", "project:list", "projectParticipation:list"})
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
     * @Groups({"project:list", "project:view"})
     *
     * @return ProjectParticipation[]|ArrayCollection
     *
     * @MaxDepth(2)
     */
    public function getParticipants(): iterable
    {
        return $this->getProjectParticipations();
    }

    /**
     * @return Collection|ProjectOrganizer[]
     *
     * @Groups({"project:view"})
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
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param string|null $image
     *
     * @return Project
     */
    public function setImage(?string $image): Project
    {
        $this->image = $image;

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
     * @Groups({"project:list", "projectParticipation:list"})
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
     * @Groups({"project:list"})
     *
     * @return array
     */
    public function getTodos(): array
    {
        $projectComplete = true;

        if (
            null === $this->getTitle()
            || null === $this->getBorrowerCompany()
            || null === $this->getMarketSegment()
            || null === $this->getSyndicationType()
            || null === $this->getParticipationType()
            || (true === $this->isSubParticipation()
                && null === $this->getRiskType())
        ) {
            $projectComplete = false;
        }

        // @todo really quick and dirty translation
        return [
            ['name' => /*'project'*/'Synthèse', 'done' => $projectComplete],
            ['name' => /*'calendar'*/'Calendrier', 'done' => null !== $this->getLenderConsultationClosingDate()],
            ['name' => /*'description'*/'Description', 'done' => null !== $this->getDescription()],
            ['name' => /*'tranches'*/'Tranches', 'done' => 0 < count($this->getTranches())],
            ['name' => /*'invitations'*/'Participants', 'done' => 0 < count($this->getProjectParticipations())],
        ];
    }

    /**
     * @return ArrayCollection|Attachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @return Attachment|null
     *
     * @Groups({"project:view"})
     */
    public function getDescriptionDocument(): ?Attachment
    {
        return $this->attachments->filter(
            static function (Attachment $attachment) {
                return Attachment::TYPE_PROJECT_DESCRIPTION === $attachment->getType();
            }
        )->first() ?: null;
    }

    /**
     * @param ArrayCollection|Attachment[] $attachments
     *
     * @return Project
     */
    public function setAttachments($attachments): Project
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * @return Money
     *
     * @Groups({"projectParticipation:list"})
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
     * @Groups({"project:list"})
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
     * @Groups({"project:view"})
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
     * @param string $role
     *
     * @return ProjectParticipation[]|Collection
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
     * @throws Exception
     *
     * @return ProjectParticipation|null
     */
    private function getUniqueOrganizer(string $role): ?ProjectOrganizer
    {
        if (false === ProjectOrganizer::isUniqueRole($role)) {
            throw new RuntimeException(sprintf('Role "%s" is not unique. Cannot get project Participation corresponding to the role.', $role));
        }

        return $this->getOrganizersByRole($role)->first() ?: null;
    }
}
