<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\Timestampable;
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
    use Timestampable;

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
     * @ORM\Column(length=191)
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
     *     @ORM\JoinColumn(name="id_market_segment", referencedColumnName="id")
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
     * @ORM\Column(type="date_immutable")
     *
     * @Assert\Date
     */
    private $replyDeadline;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable")
     *
     * @Assert\Date
     */
    private $expectedClosingDate;

    /**
     * @var ProjectStatusHistory|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\ProjectStatusHistory")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_status_history")
     * })
     */
    private $lastProjectStatusHistory;

    /**
     * @var ArrayCollection|ProjectStatusHistory[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectStatusHistory", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectStatusHistories;

    /**
     * @var ProjectAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectAttachment", mappedBy="project")
     */
    private $attachments;

    /**
     * @var ProjectParticipant[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipant", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     */
    private $projectParticipants;

    /**
     * @var Bids[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Bids", mappedBy="project")
     * @ORM\OrderBy({"added": "DESC"})
     */
    private $bids;

    /**
     * @var Loans[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Loans", mappedBy="project")
     */
    private $loans;

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
     * Project constructor.
     */
    public function __construct()
    {
        $this->attachments            = new ArrayCollection();
        $this->projectParticipants    = new ArrayCollection();
        $this->projectPercentFees     = new ArrayCollection();
        $this->comments               = new ArrayCollection();
        $this->projectStatusHistories = new ArrayCollection();
    }

    /**
     * @param string $hash
     *
     * @return Project
     */
    public function setHash($hash)
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
     *
     * @return Project
     */
    public function setSlug()
    {
        $this->slug = URLify::filter($this->title);

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
     * @param Companies|null $company
     *
     * @return Project
     */
    public function setSubmitterCompany(?Companies $company): Project
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
     * @param Clients|null $client
     *
     * @return Project
     */
    public function setSubmitterClient(?Clients $client): Project
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
     * Get idProject.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get project attachments.
     *
     * @return ProjectAttachment[]
     */
    public function getAttachments(): iterable
    {
        return $this->attachments;
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
     * @param Companies $company
     *
     * @return Project
     */
    public function addArranger(Companies $company): Project
    {
        $this->addProjectParticipant($company, ProjectParticipant::COMPANY_ROLE_ARRANGER);

        return $this;
    }

    /**
     * @param Companies $company
     *
     * @return Project
     */
    public function addRun(Companies $company): Project
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
     * @param ProjectParticipant $projectParticipant
     *
     * @return Project
     */
    public function removeProjectParticipants(ProjectParticipant $projectParticipant): Project
    {
        $this->projectParticipants->removeElement($projectParticipant);

        return $this;
    }

    /**
     * @param Companies|null $companies
     *
     * @return ProjectParticipant[]|Collection
     */
    public function getProjectParticipants(?Companies $companies = null): iterable
    {
        $criteria = new Criteria();

        if ($companies) {
            $criteria->where(Criteria::expr()->eq('company', $companies));
        }

        return $this->projectParticipants->matching($criteria);
    }

    /**
     * @return ProjectParticipant|null
     */
    public function getArrangerParticipant(): ?ProjectParticipant
    {
        return $this->getParticipant(ProjectParticipant::COMPANY_ROLE_ARRANGER);
    }

    /**
     * @return ProjectParticipant|null
     */
    public function getRunParticipant(): ?ProjectParticipant
    {
        return $this->getParticipant(ProjectParticipant::COMPANY_ROLE_RUN);
    }

    /**
     * @return Companies[]
     */
    public function getLenders(): iterable
    {
        $lenders = [];

        foreach ($this->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole(ProjectParticipant::COMPANY_ROLE_LENDER)) {
                $lenders[] = $projectParticipant->getCompany();
            }
        }

        return $lenders;
    }

    /**
     * @param int|null $status
     *
     * @return Bids[]|ArrayCollection
     */
    public function getBids(?int $status = null): iterable
    {
        $criteria = new Criteria();

        if (null !== $status) {
            $criteria->where(Criteria::expr()->eq('status', $status));
        }

        return $this->bids->matching($criteria);
    }

    /**
     * @return Loans[]|ArrayCollection
     */
    public function getLoans(): iterable
    {
        return $this->loans;
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
     * @return iterable|ProjectPercentFee[]
     */
    public function getProjectPercentFees(): iterable
    {
        return $this->projectPercentFees;
    }

    /**
     * @return ProjectComment[]|ArrayCollection
     */
    public function getComments(): iterable
    {
        return $this->comments;
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
     * @param DateTimeImmutable $replyDeadline
     *
     * @return Project
     */
    public function setReplyDeadline(DateTimeImmutable $replyDeadline): Project
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
     * @param DateTimeImmutable $expectedClosingDate
     *
     * @return Project
     */
    public function setExpectedClosingDate(DateTimeImmutable $expectedClosingDate): Project
    {
        $this->expectedClosingDate = $expectedClosingDate;

        return $this;
    }

    /**
     * @return ProjectStatusHistory|null
     */
    public function getLastProjectStatusHistory(): ?ProjectStatusHistory
    {
        return $this->lastProjectStatusHistory;
    }

    /**
     * @param ProjectStatusHistory $lastProjectStatusHistory
     *
     * @return Project
     */
    public function setLastProjectStatusHistory(ProjectStatusHistory $lastProjectStatusHistory): Project
    {
        $this->lastProjectStatusHistory = $lastProjectStatusHistory;

        return $this;
    }

    /**
     * @param ProjectStatusHistory $projectStatusHistory
     *
     * @return $this
     */
    public function addProjectStatusHistory(ProjectStatusHistory $projectStatusHistory): Project
    {
        $projectStatusHistory->setProject($this);

        if (false === $this->projectStatusHistories->contains($projectStatusHistory)) {
            $this->projectStatusHistories->add($projectStatusHistory);
        }

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
    private function hasRole(string $role): bool
    {
        foreach ($this->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Companies $company
     * @param string    $role
     */
    private function addProjectParticipant(Companies $company, string $role): void
    {
        if (false === $this->isUniqueRole($role) || false === $this->hasRole($role)) {
            $projectParticipants = $this->getProjectParticipants($company);

            if ($projectParticipants->count()) {
                $projectParticipant = $projectParticipants->first();
            }

            if (empty($projectParticipant)) {
                $projectParticipant = (new ProjectParticipant())
                    ->setCompany($company)
                    ->setProject($this)
                ;
            }

            $projectParticipant->addRoles([$role]);
            $this->projectParticipants->add($projectParticipant);
        }
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
     * @return ProjectParticipant|null
     */
    private function getParticipant(string $role): ?ProjectParticipant
    {
        foreach ($this->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole($role)) {
                return $projectParticipant;
            }
        }

        return null;
    }
}
