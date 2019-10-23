<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Entity\Traits\TraceableStatusTrait;
use Unilend\Service\User\RealUserFinder;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     attributes={"security": "is_granted('ROLE_USER')"},
 *     collectionOperations={
 *         "get",
 *         "post"
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object)"},
 *         "put": {"security": "is_granted('edit', object)"},
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

    public const OFFER_VISIBILITY_PUBLIC  = 1;
    public const OFFER_VISIBILITY_PRIVATE = 2;

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
     */
    private $borrowerCompany;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_company_submitter", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $submitterCompany;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client_submitter",  referencedColumnName="id_client", nullable=false)
     * })
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
     */
    private $marketSegment;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215)
     *
     * @Assert\NotBlank
     *
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @Gedmo\Versioned
     */
    private $confidential = false;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
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
     */
    private $lenderConsultationClosingDate;

    /**
     * @var string|null
     *
     * @ORM\Column(length=8, nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $internalRatingScore;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=false)
     *
     * @Gedmo\Versioned
     */
    private $offerVisibility;

    /**
     * @var ProjectAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectAttachment", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectAttachments;

    /**
     * @var ProjectParticipation[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipation", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipations;

    /**
     * @var ProjectFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectFee", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectFees;

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
     * @Assert\Count(min="1", minMessage="project.tranche.count")
     *
     * @Assert\Valid
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
     */
    private $currentStatus;

    /**
     * @var ArrayCollection|ProjectStatus
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
     */
    private $riskType;

    /**
     * @var ArrayCollection|ProjectOffer
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectOffer", mappedBy="project", orphanRemoval=true, cascade={"persist"})
     */
    private $projectOffers;

    /**
     * Project constructor.
     *
     * @param RealUserFinder $submitterClient
     */
    public function __construct(RealUserFinder $submitterClient)
    {
        $this->projectAttachments         = new ArrayCollection();
        $this->projectParticipations      = new ArrayCollection();
        $this->projectFees                = new ArrayCollection();
        $this->comments                   = new ArrayCollection();
        $this->statuses                   = new ArrayCollection();
        $this->tranches                   = new ArrayCollection();
        $this->confidentialityAcceptances = new ArrayCollection();
        $this->projectOffers              = new ArrayCollection();

        $this->setCurrentStatus(ProjectStatus::STATUS_REQUESTED, $submitterClient);

        $this->syndicationType   = static::PROJECT_SYNDICATION_TYPE_PRIMARY;
        $this->participationType = static::PROJECT_PARTICIPATION_TYPE_DIRECT;
        $this->offerVisibility   = static::OFFER_VISIBILITY_PUBLIC;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $hash
     *
     * @return Project
     */
    public function setHash(string $hash): Project
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @ORM\PrePersist
     */
    public function setHashValue(): void
    {
        if (null === $this->hash) {
            try {
                $this->hash = $this->generateHash();
            } catch (Exception $e) {
                $this->hash = md5(uniqid('', false));
            }
        }
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
     * @return Companies|null
     */
    public function getBorrowerCompany(): ?Companies
    {
        return $this->borrowerCompany;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function setSubmitterCompany(Companies $company): Project
    {
        $this->submitterCompany = $company;

        return $this;
    }

    /**
     * @return Companies|null
     */
    public function getSubmitterCompany(): ?Companies
    {
        return $this->submitterCompany;
    }

    /**
     * @param Clients $client
     *
     * @return Project
     */
    public function setSubmitterClient(Clients $client): Project
    {
        $this->submitterClient = $client;

        return $this;
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
    public function setDescription($description): Project
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
     * @param int            $status
     * @param RealUserFinder $realUserFinder
     *
     * @return Project
     */
    public function setCurrentStatus(int $status, RealUserFinder $realUserFinder): self
    {
        $projectStatus = new ProjectStatus($this, $status, $realUserFinder);

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
        if (in_array($internalRatingScore, $this->getAllInternalRatingScores(), true)) {
            $this->internalRatingScore = $internalRatingScore;
        }
    }

    /**
     * @return array
     */
    public function getAllInternalRatingScores(): array
    {
        return self::getConstants('INTERNAL_RATING_SCORE_');
    }

    /**
     * @return int
     */
    public function getOfferVisibility(): ?int
    {
        return $this->offerVisibility;
    }

    /**
     * @param int $offerVisibility
     *
     * @return Project
     */
    public function setOfferVisibility(int $offerVisibility): Project
    {
        $this->offerVisibility = $offerVisibility;

        return $this;
    }

    /**
     * @return iterable
     */
    public static function getAllOfferVisibilities(): iterable
    {
        return self::getConstants('OFFER_VISIBILITY_');
    }

    /**
     * @return ProjectAttachment[]
     */
    public function getProjectAttachments(): iterable
    {
        return $this->projectAttachments;
    }

    /**
     * @param ProjectAttachment $projectAttachment
     *
     * @return Project
     */
    public function addProjectAttachment(ProjectAttachment $projectAttachment): Project
    {
        $projectAttachment->setProject($this);

        if (false === $this->projectAttachments->contains($projectAttachment)) {
            $this->projectAttachments->add($projectAttachment);
        }

        return $this;
    }

    /**
     * @param ProjectAttachment $projectAttachment
     *
     * @return Project
     */
    public function removeProjectAttachment(ProjectAttachment $projectAttachment): Project
    {
        $this->projectAttachments->removeElement($projectAttachment);

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
        if ($this->isUniqueRole($role)) {
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
     * @return iterable|ProjectFee[]
     */
    public function getProjectFees(): iterable
    {
        return $this->projectFees;
    }

    /**
     * @param ProjectFee $projectFee
     *
     * @return Project
     */
    public function addProjectFee(ProjectFee $projectFee): Project
    {
        $projectFee->setProject($this);

        if (false === $this->projectFees->contains($projectFee)) {
            $this->projectFees->add($projectFee);
        }

        return $this;
    }

    /**
     * @param ProjectFee $projectFee
     *
     * @return Project
     */
    public function removeProjectFee(ProjectFee $projectFee): Project
    {
        if ($this->projectFees->contains($projectFee)) {
            $this->projectFees->removeElement($projectFee);
        }

        return $this;
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
     * @throws Exception
     *
     * @return string
     */
    private function generateHash(): string
    {
        $uuid4 = Uuid::uuid4();

        return $uuid4->toString();
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    private function isUniqueRole(string $role): bool
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
        if (false === $this->isUniqueRole($role)) {
            throw new RuntimeException(sprintf('Role "%s" is not unique. Cannot get project Participation corresponding to the role.', $role));
        }

        return $this->getParticipationsByRole($role)->first() ?: null;
    }
}
