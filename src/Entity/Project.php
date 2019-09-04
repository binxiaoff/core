<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;
use URLify;

/**
 * @ORM\Table(indexes={
 *     @ORM\Index(name="slug", columns={"slug"}),
 *     @ORM\Index(name="hash", columns={"hash"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Project
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const OPERATION_TYPE_ARRANGEMENT = 1;
    public const OPERATION_TYPE_SYNDICATION = 2;

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

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(length=36)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     */
    private $slug;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_borrower_company", referencedColumnName="id_company", nullable=false)
     * })
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
     */
    private $marketSegment;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215)
     *
     * @Assert\NotBlank
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $confidential = false;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $confidentialityDisclaimer;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     */
    private $replyDeadline;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     */
    private $expectedClosingDate;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     */
    private $lenderConsultationClosingDate;

    /**
     * @var ProjectStatusHistory|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectStatusHistory")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_status_history")
     * })
     */
    private $currentProjectStatusHistory;

    /**
     * @var string|null
     *
     * @ORM\Column(length=8, nullable=true)
     */
    private $internalRatingScore;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    private $operationType;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    private $offerVisibility;

    /**
     * @var ArrayCollection|ProjectStatusHistory[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectStatusHistory", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectStatusHistories;

    /**
     * @var ProjectAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectAttachment", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectAttachments;

    /**
     * @var ProjectParticipant[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipant", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipants;

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
     * Project constructor.
     */
    public function __construct()
    {
        $this->projectAttachments         = new ArrayCollection();
        $this->projectParticipants        = new ArrayCollection();
        $this->projectFees                = new ArrayCollection();
        $this->comments                   = new ArrayCollection();
        $this->projectStatusHistories     = new ArrayCollection();
        $this->tranches                   = new ArrayCollection();
        $this->confidentialityAcceptances = new ArrayCollection();
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
    public function setHashValue()
    {
        if (null === $this->hash) {
            try {
                $this->hash = $this->generateHash();
            } catch (Exception $e) {
                $this->hash = md5(uniqid());
            }
        }
    }

    /**
     * @ORM\PrePersist
     *
     * @return Project
     */
    public function setSlug()
    {
        try {
            $this->slug = URLify::filter($this->title) . '-' . mb_substr(Uuid::uuid4()->toString(), 0, 8);
        } catch (Exception $e) {
            $this->slug = URLify::filter($this->title) . '-' . mb_substr(uniqid(), 0, 8);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function setBorrowerCompany(Companies $company)
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
    public function setTitle($title)
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
    public function setDescription($description)
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
     * @return ProjectStatusHistory|null
     */
    public function getCurrentProjectStatusHistory(): ?ProjectStatusHistory
    {
        return $this->currentProjectStatusHistory;
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
        if (in_array($internalRatingScore, $this->getAllInternalRatingScores())) {
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
     * @return int|null
     */
    public function getOperationType(): ?int
    {
        return $this->operationType;
    }

    /**
     * @param int $operationType
     *
     * @return Project
     */
    public function setOperationType(int $operationType): Project
    {
        if (in_array($operationType, $this->getAllOperationTypes())) {
            $this->operationType = $operationType;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getAllOperationTypes(): array
    {
        return self::getConstants('OPERATION_TYPE_');
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
     * @return ProjectParticipant[]|Collection
     */
    public function getProjectParticipants(): iterable
    {
        return $this->projectParticipants;
    }

    /**
     * @param Companies $companies
     *
     * @return ProjectParticipant|null
     */
    public function getProjectParticipantByCompany(Companies $companies): ?ProjectParticipant
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq('company', $companies));

        // A company can only have one participant on a project.
        return $this->projectParticipants->matching($criteria)->first() ?: null;
    }

    /**
     * @param Companies $company
     * @param string    $role
     *
     * @return Project
     */
    public function addProjectParticipant(Companies $company, string $role): Project
    {
        if ($this->isUniqueRole($role)) {
            /** @var ProjectParticipant $projectParticipantToDelete */
            $projectParticipantToDelete = $this->getParticipantsByRole($role)->first();

            if ($projectParticipantToDelete && $company !== $projectParticipantToDelete->getCompany()) {
                $projectParticipantToDelete->removeRole($role);
            }
        }

        $projectParticipant = $this->getProjectParticipantByCompany($company);

        if (null === $projectParticipant) {
            $projectParticipant = (new ProjectParticipant())
                ->setCompany($company)
                ->setProject($this)
            ;
        }

        $projectParticipant->addRoles([$role]);

        if (false === $this->projectParticipants->contains($projectParticipant)) {
            $this->projectParticipants->add($projectParticipant);
        }

        return $this;
    }

    /**
     * @param ProjectParticipant $projectParticipant
     *
     * @return Project
     */
    public function removeProjectParticipant(ProjectParticipant $projectParticipant): Project
    {
        $this->projectParticipants->removeElement($projectParticipant);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function setArranger(Companies $company): Project
    {
        $this->addProjectParticipant($company, ProjectParticipant::ROLE_PROJECT_ARRANGER);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function setDeputyArranger(Companies $company): Project
    {
        $this->addProjectParticipant($company, ProjectParticipant::ROLE_PROJECT_DEPUTY_ARRANGER);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function setRun(Companies $company): Project
    {
        $this->addProjectParticipant($company, ProjectParticipant::ROLE_PROJECT_RUN);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function setLoanOfficer(Companies $company): Project
    {
        $this->addProjectParticipant($company, ProjectParticipant::ROLE_PROJECT_LOAN_OFFICER);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function setSecurityTrustee(Companies $company): Project
    {
        $this->addProjectParticipant($company, ProjectParticipant::ROLE_PROJECT_SECURITY_TRUSTEE);

        return $this;
    }

    /**
     * @param Companies[] $companies
     *
     * @return Project
     */
    public function addLenders(array $companies): Project
    {
        foreach ($companies as $company) {
            $this->addProjectParticipant($company, ProjectParticipant::ROLE_PROJECT_LENDER);
        }

        return $this;
    }

    /**
     * @param Companies[] $companies
     *
     * @return Project
     */
    public function setLenders(array $companies): Project
    {
        foreach ($this->getLenders() as $lender) {
            if (false === in_array($lender->getCompany(), $companies)) {
                $lender->removeRole(ProjectParticipant::ROLE_PROJECT_LENDER);
            }
        }

        return $this->addLenders($companies);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipant|null
     */
    public function getArranger(): ?ProjectParticipant
    {
        return $this->getUniqueRoleParticipant(ProjectParticipant::ROLE_PROJECT_ARRANGER);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipant|null
     */
    public function getDeputyArranger(): ?ProjectParticipant
    {
        return $this->getUniqueRoleParticipant(ProjectParticipant::ROLE_PROJECT_DEPUTY_ARRANGER);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipant|null
     */
    public function getRun(): ?ProjectParticipant
    {
        return $this->getUniqueRoleParticipant(ProjectParticipant::ROLE_PROJECT_RUN);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipant|null
     */
    public function getLoanOfficer(): ?ProjectParticipant
    {
        return $this->getUniqueRoleParticipant(ProjectParticipant::ROLE_PROJECT_LOAN_OFFICER);
    }

    /**
     * @throws Exception
     *
     * @return ProjectParticipant|null
     */
    public function getSecurityTrustee(): ?ProjectParticipant
    {
        return $this->getUniqueRoleParticipant(ProjectParticipant::ROLE_PROJECT_SECURITY_TRUSTEE);
    }

    /**
     * @return ProjectParticipant[]|ArrayCollection
     */
    public function getLenders(): iterable
    {
        return $this->getParticipantsByRole(ProjectParticipant::ROLE_PROJECT_LENDER);
    }

    /**
     * @return Companies[]|ArrayCollection
     */
    public function getLenderCompanies(): iterable
    {
        return $this->getCompaniesByRole(ProjectParticipant::ROLE_PROJECT_LENDER);
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
     * @return ArrayCollection|ProjectStatusHistory[]
     */
    public function getProjectStatusHistories(): iterable
    {
        return $this->projectStatusHistories;
    }

    /**
     * @param ProjectStatusHistory $projectStatusHistory
     *
     * @return Project
     */
    public function setProjectStatusHistory(ProjectStatusHistory $projectStatusHistory): Project
    {
        $projectStatusHistory->setProject($this);

        if (null === $this->currentProjectStatusHistory || $this->currentProjectStatusHistory->getStatus() !== $projectStatusHistory->getStatus()) {
            $this->projectStatusHistories->add($projectStatusHistory);
            $this->setCurrentProjectStatusHistory($projectStatusHistory);
        }

        return $this;
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
        $this->projectParticipants->removeElement($tranche);

        return $this;
    }

    /**
     * @param array|null     $status
     * @param Companies|null $lender
     *
     * @return Bids[]|ArrayCollection
     */
    public function getBids(?array $status = null, ?Companies $lender = null): iterable
    {
        $bids = [];
        foreach ($this->getTranches() as $tranche) {
            $bids = array_merge($bids, $tranche->getBids($status, $lender)->toArray());
        }

        return new ArrayCollection($bids);
    }

    /**
     * @return Loans[]|ArrayCollection
     */
    public function getLoans(): iterable
    {
        $loans = [];
        foreach ($this->getTranches() as $tranche) {
            array_push($loans, ...$tranche->getLoans()->toArray());
        }

        return new ArrayCollection($loans);
    }

    /**
     * @return bool
     */
    public function isEditable(): bool
    {
        if ($this->getCurrentProjectStatusHistory()) {
            return $this->getCurrentProjectStatusHistory()->getStatus() < ProjectStatusHistory::STATUS_PUBLISHED;
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
        return ProjectStatusHistory::STATUS_PUBLISHED === $this->getCurrentProjectStatusHistory()->getStatus();
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
     * @param ProjectStatusHistory $currentProjectStatusHistory
     *
     * @return Project
     */
    private function setCurrentProjectStatusHistory(ProjectStatusHistory $currentProjectStatusHistory): Project
    {
        $this->currentProjectStatusHistory = $currentProjectStatusHistory;

        return $this;
    }

    /**
     * @throws Exception
     *
     * @return string
     */
    private function generateHash()
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
            ProjectParticipant::ROLE_PROJECT_ARRANGER,
            ProjectParticipant::ROLE_PROJECT_DEPUTY_ARRANGER,
            ProjectParticipant::ROLE_PROJECT_RUN,
            ProjectParticipant::ROLE_PROJECT_LOAN_OFFICER,
            ProjectParticipant::ROLE_PROJECT_SECURITY_TRUSTEE,
        ]);
    }

    /**
     * @param string $role
     *
     * @return ProjectParticipant[]|ArrayCollection
     */
    private function getParticipantsByRole(string $role): iterable
    {
        $isUniqueRole = $this->isUniqueRole($role);

        $projectParticipants = new ArrayCollection();

        // Ugly foreach on the participants (hopefully we don't have many participants on a project), as the Criteria doesn't support the json syntax.
        foreach ($this->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole($role)) {
                $projectParticipants->add($projectParticipant);
                if ($isUniqueRole) {
                    break;
                }
            }
        }

        return $projectParticipants;
    }

    /**
     * @param string $role
     *
     * @return ProjectParticipant[]|ArrayCollection
     */
    private function getCompaniesByRole(string $role): iterable
    {
        $isUniqueRole = $this->isUniqueRole($role);

        $companies = new ArrayCollection();

        // Ugly foreach on the participants (hopefully we don't have many participants on a project), as the Criteria doesn't support the json syntax.
        foreach ($this->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole($role)) {
                $companies->add($projectParticipant->getCompany());
                if ($isUniqueRole) {
                    break;
                }
            }
        }

        return $companies;
    }

    /**
     * @param string $role
     *
     * @throws Exception
     *
     * @return ProjectParticipant|null
     */
    private function getUniqueRoleParticipant(string $role): ?ProjectParticipant
    {
        if (false === $this->isUniqueRole($role)) {
            throw new Exception(sprintf('Role "%s" is not unique. Cannot get project participant corresponding to the role.', $role));
        }

        return $this->getParticipantsByRole($role)->first() ?: null;
    }
}
