<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria, ExpressionBuilder};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Entity\Traits\TraceableStatusTrait;
use Unilend\Service\User\RealUserFinder;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get",
 *         "post": {"denormalization_context": {"groups": {"project:create"}}}
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object)"},
 *         "put": {"security": "is_granted('edit', object)", "denormalization_context": {"groups": {"project:update"}}}
 *     }
 * )
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
     */
    private $hash;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_borrower_company", referencedColumnName="id_company", nullable=false)
     * })
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update"})
     *
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private $borrowerCompany;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company_submitter", referencedColumnName="id_company", nullable=false)
     * })
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
     * @Groups({"project:create", "project:update"})
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
     * @Groups({"project:create", "project:update"})
     */
    private $marketSegment;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update"})
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update"})
     */
    private $confidential = false;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update"})
     */
    private $confidentialityDisclaimer;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update"})
     */
    private $replyDeadline;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update"})
     */
    private $expectedClosingDate;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:create", "project:update"})
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
     * @Groups({"project:create", "project:update"})
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
     * @Groups({"project:create", "project:update"})
     */
    private $offerVisibility;

    /**
     * @var Attachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectAttachment", mappedBy="project")
     *
     * @ApiSubresource
     */
    private $attachments;

    /**
     * @var ProjectParticipation[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipation", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipations;

    /**
     * @var ProjectComment[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectComment", mappedBy="project")
     * @ORM\OrderBy({"added": "DESC"})
     */
    private $comments;

    /**
     * @var Tranche[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Tranche", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @Assert\Valid
     *
     * @Groups({"project:create"})
     */
    private $tranches;

    /**
     * @var ProjectConfidentialityAcceptance[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectConfidentialityAcceptance", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $confidentialityAcceptances;

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
     * @Groups({"project:create", "project:update"})
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
     * @Groups({"project:create", "project:update"})
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
     * @Groups({"project:create", "project:update"})
     */
    private $riskType;

    /**
     * @var ArrayCollection|ProjectOffer
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectOffer", mappedBy="project", orphanRemoval=true, cascade={"persist"})
     */
    private $projectOffers;

    /**
     * @var Collection|Tag[]
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Entity\Tag", cascade={"persist"})
     */
    private $tags;

    /**
     * @param Clients   $submitter
     * @param Companies $borrowerCompany
     */
    public function __construct(Clients $submitter, Companies $borrowerCompany)
    {
        $this->attachments                = new ArrayCollection();
        $this->projectParticipations      = new ArrayCollection();
        $this->comments                   = new ArrayCollection();
        $this->statuses                   = new ArrayCollection();
        $this->tranches                   = new ArrayCollection();
        $this->confidentialityAcceptances = new ArrayCollection();
        $this->projectOffers              = new ArrayCollection();
        $this->tags                       = new ArrayCollection();

        $this->setCurrentStatus(ProjectStatus::STATUS_REQUESTED, $submitter);

        $this->submitterClient  = $submitter;
        $this->submitterCompany = $submitter->getCompany();

        $this->syndicationType   = static::PROJECT_SYNDICATION_TYPE_PRIMARY;
        $this->participationType = static::PROJECT_PARTICIPATION_TYPE_DIRECT;
        $this->offerVisibility   = static::OFFER_VISIBILITY_PUBLIC;

        $this->borrowerCompany = $borrowerCompany;

        if (null === $this->hash) {
            try {
                $this->hash = (string) (Uuid::uuid4());
            } catch (Throwable $e) {
                $this->hash = md5(uniqid('', false));
            }
        }
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
     * @param int     $status
     * @param Clients $clients
     *
     * @return Project
     */
    public function setCurrentStatus(int $status, Clients $clients): self
    {
        $projectStatus = new ProjectStatus($this, $status, $clients);

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
     * @return int
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
     * @return Attachment[]
     */
    public function getAttachments(): iterable
    {
        return $this->attachments;
    }

    /**
     * @param AttachmentType $type
     *
     * @return ArrayCollection|Collection
     */
    public function getAttachmentByAttachmentType(AttachmentType $type): Collection
    {
        return $this->attachments->matching(
            (new Criteria())->where((new ExpressionBuilder())->eq('type', $type))
        );
    }

    /**
     * @param ProjectAttachmentType $projectType
     *
     * @return ArrayCollection|Collection
     */
    public function getAttachmentByProjectAttachmentType(ProjectAttachmentType $projectType)
    {
        return $this->getAttachmentByAttachmentType($projectType->getAttachmentType());
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
     * @param Companies      $company
     * @param string         $role
     * @param RealUserFinder $realUserFinder
     *
     * @return ProjectParticipation
     */
    public function addProjectParticipation(Companies $company, string $role, RealUserFinder $realUserFinder): ProjectParticipation
    {
        if (static::isUniqueRole($role)) {
            /** @var ProjectParticipation $projectParticipationToDelete */
            $projectParticipationToDelete = $this->getParticipationsByRole($role)->first();

            if ($projectParticipationToDelete && $company !== $projectParticipationToDelete->getCompany()) {
                $projectParticipationToDelete->removeRole($role);
            }
        }

        $projectParticipation = $this->getProjectParticipationByCompany($company);

        if (null === $projectParticipation) {
            $projectParticipation = (new ProjectParticipation())
                ->setCompany($company)
                ->setProject($this)
                ->setAddedByValue($realUserFinder)
            ;
        }

        $projectParticipation->addRoles([$role]);

        if (false === $this->projectParticipations->contains($projectParticipation)) {
            $this->projectParticipations->add($projectParticipation);
        }

        return $projectParticipation;
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
     * @param Companies      $company
     * @param RealUserFinder $realUserFinder
     *
     * @return ProjectParticipation
     */
    public function setArranger(Companies $company, RealUserFinder $realUserFinder): ProjectParticipation
    {
        return $this->addProjectParticipation($company, ProjectParticipation::DUTY_PROJECT_PARTICIPATION_ARRANGER, $realUserFinder);
    }

    /**
     * @param Companies      $company
     * @param RealUserFinder $realUserFinder
     *
     * @return ProjectParticipation
     */
    public function setDeputyArranger(Companies $company, RealUserFinder $realUserFinder): ProjectParticipation
    {
        return $this->addProjectParticipation($company, ProjectParticipation::DUTY_PROJECT_PARTICIPATION_DEPUTY_ARRANGER, $realUserFinder);
    }

    /**
     * @param Companies      $company
     * @param RealUserFinder $realUserFinder
     *
     * @return ProjectParticipation
     */
    public function setRun(Companies $company, RealUserFinder $realUserFinder): ProjectParticipation
    {
        return $this->addProjectParticipation($company, ProjectParticipation::DUTY_PROJECT_PARTICIPATION_RUN, $realUserFinder);
    }

    /**
     * @param Companies      $company
     * @param RealUserFinder $realUserFinder
     *
     * @return ProjectParticipation
     */
    public function setLoanOfficer(Companies $company, RealUserFinder $realUserFinder): ProjectParticipation
    {
        return $this->addProjectParticipation($company, ProjectParticipation::DUTY_PROJECT_PARTICIPATION_LOAN_OFFICER, $realUserFinder);
    }

    /**
     * @param Companies      $company
     * @param RealUserFinder $realUserFinder
     *
     * @return ProjectParticipation
     */
    public function setSecurityTrustee(Companies $company, RealUserFinder $realUserFinder): ProjectParticipation
    {
        return $this->addProjectParticipation($company, ProjectParticipation::DUTY_PROJECT_PARTICIPATION_SECURITY_TRUSTEE, $realUserFinder);
    }

    /**
     * @param Companies      $company
     * @param RealUserFinder $realUserFinder
     *
     * @return ProjectParticipation
     */
    public function addParticipant(Companies $company, RealUserFinder $realUserFinder): ProjectParticipation
    {
        return $this->addProjectParticipation($company, ProjectParticipation::DUTY_PROJECT_PARTICIPATION_PARTICIPANT, $realUserFinder);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipation|null
     */
    public function getArranger(): ?ProjectParticipation
    {
        return $this->getUniqueRoleParticipation(ProjectParticipation::DUTY_PROJECT_PARTICIPATION_ARRANGER);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipation|null
     */
    public function getDeputyArranger(): ?ProjectParticipation
    {
        return $this->getUniqueRoleParticipation(ProjectParticipation::DUTY_PROJECT_PARTICIPATION_DEPUTY_ARRANGER);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipation|null
     */
    public function getRun(): ?ProjectParticipation
    {
        return $this->getUniqueRoleParticipation(ProjectParticipation::DUTY_PROJECT_PARTICIPATION_RUN);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipation|null
     */
    public function getLoanOfficer(): ?ProjectParticipation
    {
        return $this->getUniqueRoleParticipation(ProjectParticipation::DUTY_PROJECT_PARTICIPATION_LOAN_OFFICER);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipation|null
     */
    public function getSecurityTrustee(): ?ProjectParticipation
    {
        return $this->getUniqueRoleParticipation(ProjectParticipation::DUTY_PROJECT_PARTICIPATION_SECURITY_TRUSTEE);
    }

    /**
     * @return ProjectParticipation[]|ArrayCollection
     */
    public function getParticipants(): iterable
    {
        return $this->getParticipationsByRole(ProjectParticipation::DUTY_PROJECT_PARTICIPATION_PARTICIPANT);
    }

    /**
     * @return Companies[]|ArrayCollection
     */
    public function getLenderCompanies(): iterable
    {
        return $this->getCompaniesByRole(ProjectParticipation::DUTY_PROJECT_PARTICIPATION_PARTICIPANT);
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
     * @param array|null     $status
     * @param Companies|null $lender
     *
     * @return TrancheOffer[]|ArrayCollection
     */
    public function getTrancheOffers(?array $status = null, ?Companies $lender = null): ArrayCollection
    {
        $trancheOffers = [];
        $projectOffer  = $this->getProjectOffers(null, $lender)->first();
        if (false === $projectOffer) {
            return new ArrayCollection();
        }

        foreach ($this->getTranches() as $tranche) {
            array_push($trancheOffers, ...$tranche->getTrancheOffer($status, $projectOffer)->toArray());
        }

        return new ArrayCollection($trancheOffers);
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
     * @param Clients $user
     *
     * @return bool
     */
    public function checkUserConfidentiality(Clients $user): bool
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq('client', $user));

        return
            false                  === $this->isConfidential()
            || $user->getCompany() === $this->getSubmitterCompany()
            || false               === $this->confidentialityAcceptances->matching($criteria)->isEmpty()
        ;
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
     * @param ProjectOffer $projectOffer
     *
     * @return Project
     */
    public function addProjectOffers(ProjectOffer $projectOffer): Project
    {
        $projectOffer->setProject($this);

        if (false === $this->projectOffers->contains($projectOffer)) {
            $this->projectOffers->add($projectOffer);
        }

        return $this;
    }

    /**
     * @param ProjectOffer $projectOffer
     *
     * @return Project
     */
    public function removeProjectOffers(ProjectOffer $projectOffer): Project
    {
        if ($this->projectOffers->contains($projectOffer)) {
            $this->projectOffers->removeElement($projectOffer);
        }

        return $this;
    }

    /**
     * @param array|null     $committeeStatus
     * @param Companies|null $lender
     *
     * @return ArrayCollection|ProjectOffer[]
     */
    public function getProjectOffers(?array $committeeStatus = null, ?Companies $lender = null): ArrayCollection
    {
        $criteria = new Criteria();

        if (null !== $committeeStatus) {
            $criteria->andWhere(Criteria::expr()->in('committeeStatus', $committeeStatus));
        }

        if (null !== $lender) {
            $criteria->andWhere(Criteria::expr()->eq('lender', $lender));
        }

        return $this->projectOffers->matching($criteria);
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    private static function isUniqueRole(string $role): bool
    {
        return in_array($role, [
            ProjectParticipation::DUTY_PROJECT_PARTICIPATION_ARRANGER,
            ProjectParticipation::DUTY_PROJECT_PARTICIPATION_DEPUTY_ARRANGER,
            ProjectParticipation::DUTY_PROJECT_PARTICIPATION_RUN,
            ProjectParticipation::DUTY_PROJECT_PARTICIPATION_LOAN_OFFICER,
            ProjectParticipation::DUTY_PROJECT_PARTICIPATION_SECURITY_TRUSTEE,
        ], true);
    }

    /**
     * @param string $role
     *
     * @return ProjectParticipation[]|Collection
     */
    private function getParticipationsByRole(string $role): Collection
    {
        // Ugly foreach on the Participations (hopefully we don't have many Participations on a project), as the Criteria doesn't support the json syntax.
        return $this->getProjectParticipations()->filter(
            static function (ProjectParticipation $participation) use ($role) {
                return $participation->hasRole($role);
            }
        );
    }

    /**
     * @param string $role
     *
     * @return ProjectParticipation[]|ArrayCollection
     */
    private function getCompaniesByRole(string $role): iterable
    {
        return $this->getParticipationsByRole($role)->map(
            static function (ProjectParticipation $participation) {
                return $participation->getCompany();
            }
        );
    }

    /**
     * @param string $role
     *
     * @throws Exception
     *
     * @return ProjectParticipation|null
     */
    private function getUniqueRoleParticipation(string $role): ?ProjectParticipation
    {
        if (false === static::isUniqueRole($role)) {
            throw new RuntimeException(sprintf('Role "%s" is not unique. Cannot get project Participation corresponding to the role.', $role));
        }

        return $this->getParticipationsByRole($role)->first() ?: null;
    }
}
