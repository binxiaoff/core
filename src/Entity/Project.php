<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\ConstantsAwareTrait;
use Unilend\Entity\Traits\TimestampableTrait;
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

    public const FONCARIS_GUARANTEE_NO_NEED            = 0;
    public const FONCARIS_GUARANTEE_NEED               = 1;
    public const FONCARIS_GUARANTEE_ALREADY_GUARANTEED = 2;

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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
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
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $foncarisGuarantee;

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
     * @var ProjectPercentFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectPercentFee", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectPercentFees;

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
     * Project constructor.
     */
    public function __construct()
    {
        $this->projectAttachments     = new ArrayCollection();
        $this->projectParticipants    = new ArrayCollection();
        $this->projectPercentFees     = new ArrayCollection();
        $this->comments               = new ArrayCollection();
        $this->projectStatusHistories = new ArrayCollection();
        $this->tranches               = new ArrayCollection();
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
     * @return ProjectStatusHistory|null
     */
    public function getCurrentProjectStatusHistory(): ?ProjectStatusHistory
    {
        return $this->currentProjectStatusHistory;
    }

    /**
     * @return int|null
     */
    public function getFoncarisGuarantee(): ?int
    {
        return $this->foncarisGuarantee;
    }

    /**
     * @param int|null $foncarisGuarantee
     *
     * @return Project
     */
    public function setFoncarisGuarantee(?int $foncarisGuarantee): Project
    {
        $this->foncarisGuarantee = $foncarisGuarantee;

        return $this;
    }

    /**
     * @return array
     */
    public static function getFoncarisGuaranteeOptions(): array
    {
        return self::getConstants('FONCARIS_GUARANTEE_');
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
        $this->addProjectParticipant($company, ProjectParticipant::COMPANY_ROLE_ARRANGER);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function setRun(Companies $company): Project
    {
        $this->addProjectParticipant($company, ProjectParticipant::COMPANY_ROLE_RUN);

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
            $this->addProjectParticipant($company, ProjectParticipant::COMPANY_ROLE_LENDER);
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
                $lender->removeRole(ProjectParticipant::COMPANY_ROLE_LENDER);
            }
        }

        return $this->addLenders($companies);
    }

    /**
     * @return ProjectParticipant|null
     */
    public function getArranger(): ?ProjectParticipant
    {
        return $this->getParticipantsByRole(ProjectParticipant::COMPANY_ROLE_ARRANGER)->first() ?: null;
    }

    /**
     * @return ProjectParticipant|null
     */
    public function getRun(): ?ProjectParticipant
    {
        return $this->getParticipantsByRole(ProjectParticipant::COMPANY_ROLE_RUN)->first() ?: null;
    }

    /**
     * @return ProjectParticipant[]|ArrayCollection
     */
    public function getLenders(): iterable
    {
        return $this->getParticipantsByRole(ProjectParticipant::COMPANY_ROLE_LENDER);
    }

    /**
     * @return Companies[]|ArrayCollection
     */
    public function getLenderCompanies(): iterable
    {
        return $this->getCompaniesByRole(ProjectParticipant::COMPANY_ROLE_LENDER);
    }

    /**
     * @return iterable|ProjectPercentFee[]
     */
    public function getProjectPercentFees(): iterable
    {
        return $this->projectPercentFees;
    }

    /**
     * @param ProjectPercentFee $projectPercentFee
     *
     * @return Project
     */
    public function addProjectPercentFee(ProjectPercentFee $projectPercentFee): Project
    {
        $projectPercentFee->setProject($this);

        if (false === $this->projectPercentFees->contains($projectPercentFee)) {
            $this->projectPercentFees->add($projectPercentFee);
        }

        return $this;
    }

    /**
     * @param ProjectPercentFee $projectPercentFee
     *
     * @return Project
     */
    public function removeProjectPercentFee(ProjectPercentFee $projectPercentFee): Project
    {
        if ($this->projectPercentFees->contains($projectPercentFee)) {
            $this->projectPercentFees->removeElement($projectPercentFee);
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
     * @param array|null  $status
     * @param Wallet|null $wallet
     *
     * @return Bids[]|ArrayCollection
     */
    public function getBids(?array $status = null, ?Wallet $wallet = null): iterable
    {
        $bids = [];
        foreach ($this->getTranches() as $tranche) {
            $bids = array_merge($bids, $tranche->getBids($status, $wallet)->toArray());
        }

        return new ArrayCollection($bids);
    }

    /**
     * @return Bids[]|ArrayCollection
     */
    public function getLoans(): iterable
    {
        $bids = [];
        foreach ($this->getTranches() as $tranche) {
            $bids = array_merge($bids, $tranche->getLoans()->toArray());
        }

        return new ArrayCollection($bids);
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
     * @param Clients $user
     *
     * @return bool
     */
    public function isScoringEditable(Clients $user): bool
    {
        return
            $this->isEditable()
            && $this->getRun()
            && $this->getRun()->getCompany() === $user->getCompany()
            ;
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
        return in_array($role, [ProjectParticipant::COMPANY_ROLE_ARRANGER, ProjectParticipant::COMPANY_ROLE_RUN]);
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
}
