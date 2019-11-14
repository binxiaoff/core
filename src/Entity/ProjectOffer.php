<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableTrait, TraceableBlamableUpdatedTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedProjectOffer")
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_lender"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectOffer
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use BlamableAddedTrait;
    use TraceableBlamableUpdatedTrait;

    public const COMMITTEE_STATUS_PENDED   = 'pended';
    public const COMMITTEE_STATUS_ACCEPTED = 'accepted';
    public const COMMITTEE_STATUS_REJECTED = 'rejected';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Companies
     *
     * @Groups({"project:list"})
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_lender", referencedColumnName="id", nullable=false)
     * })
     */
    private $lender;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectOffers")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var string
     *
     * @Groups({"project:list"})
     *
     * @ORM\Column(length=30)
     *
     * @Gedmo\Versioned
     */
    private $committeeStatus;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $expectedCommitteeDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $comment;

    /**
     * @var TrancheOffer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="TrancheOffer", mappedBy="projectOffer", cascade={"persist"}, orphanRemoval=true)
     */
    private $trancheOffers;

    /**
     * @param Companies $lender
     * @param Project   $project
     * @param string    $committeeStatus
     *
     * @throws \Exception
     */
    public function __construct(Companies $lender, Project $project, string $committeeStatus = self::COMMITTEE_STATUS_PENDED)
    {
        $this->lender          = $lender;
        $this->project         = $project;
        $this->committeeStatus = $committeeStatus;
        $this->trancheOffers   = new ArrayCollection();
        $this->added           = new DateTimeImmutable();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Companies
     */
    public function getLender(): Companies
    {
        return $this->lender;
    }

    /**
     * @param Companies $lender
     *
     * @return ProjectOffer
     */
    public function setLender(Companies $lender): ProjectOffer
    {
        $this->lender = $lender;

        return $this;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     *
     * @return ProjectOffer
     */
    public function setProject(Project $project): ProjectOffer
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommitteeStatus(): string
    {
        return $this->committeeStatus;
    }

    /**
     * @param string $committeeStatus
     *
     * @return ProjectOffer
     */
    public function setCommitteeStatus(string $committeeStatus): ProjectOffer
    {
        $this->committeeStatus = $committeeStatus;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getExpectedCommitteeDate(): ?DateTimeImmutable
    {
        return $this->expectedCommitteeDate;
    }

    /**
     * @param DateTimeImmutable $expectedCommitteeDate
     *
     * @return ProjectOffer
     */
    public function setExpectedCommitteeDate(?DateTimeImmutable $expectedCommitteeDate): ProjectOffer
    {
        $this->expectedCommitteeDate = $expectedCommitteeDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return (string) $this->comment;
    }

    /**
     * @param string $comment
     *
     * @return ProjectOffer
     */
    public function setComment(string $comment): ProjectOffer
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return ArrayCollection|TrancheOffer[]
     */
    public function getTrancheOffers()
    {
        return $this->trancheOffers;
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @return ProjectOffer
     */
    public function addTrancheOffer(TrancheOffer $trancheOffer): ProjectOffer
    {
        $trancheOffer->setProjectOffer($this);

        if (false === $this->trancheOffers->contains($trancheOffer)) {
            $this->trancheOffers->add($trancheOffer);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableCommitteeStatus(): array
    {
        return self::getConstants('COMMITTEE_STATUS_');
    }

    /**
     * @throws \Exception
     *
     * @return Embeddable\Money
     */
    public function getTrancheOffersMoney(): Money
    {
        $money = new Money($this->getProject()->getGlobalFundingMoney()->getCurrency());

        foreach ($this->getTrancheOffers() as $trancheOffer) {
            $money->add($trancheOffer->getMoney());
        }

        return $money;
    }
}
