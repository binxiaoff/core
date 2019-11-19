<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Embeddable\Money;
use Unilend\Entity\Traits\{BlamableAddedTrait, TimestampableTrait, TraceableBlamableUpdatedTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "post": {
 *         }
 *     }
 * )
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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_lender", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"project:list"})
     */
    private $lender;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="projectOffers")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     *
     * @Groups({"project:list"})
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:list", "project:view"})
     */
    private $committeeStatus;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:list", "project:view"})
     */
    private $expectedCommitteeDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Gedmo\Versioned
     *
     * @Groups({"project:view"})
     */
    private $comment;

    /**
     * @var TrancheOffer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="TrancheOffer", mappedBy="projectOffer", cascade={"persist"}, orphanRemoval=true)
     */
    private $trancheOffers;

    /**
     * @param Companies  $lender
     * @param Project    $project
     * @param Clients    $addedBy
     * @param Money|null $offerMoney
     * @param string     $committeeStatus
     *
     * @throws Exception
     */
    public function __construct(
        Companies $lender,
        Project $project,
        Clients $addedBy,
        Money $offerMoney = null,
        string $committeeStatus = self::COMMITTEE_STATUS_PENDED
    ) {
        $this->lender          = $lender;
        $this->project         = $project;
        $this->committeeStatus = $committeeStatus;
        $this->trancheOffers   = new ArrayCollection();
        $this->added           = new DateTimeImmutable();
        $this->addedBy         = $addedBy;

        if ($offerMoney) {
            $this->setOfferMoney($offerMoney);
        }
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
     * @throws Exception
     *
     * @return Embeddable\Money
     *
     * @Groups({"project:view"})
     */
    public function getOfferMoney(): Money
    {
        $money = new Money($this->getProject()->getGlobalFundingMoney()->getCurrency());

        foreach ($this->getTrancheOffers() as $trancheOffer) {
            $money = $money->add($trancheOffer->getMoney());
        }

        return $money;
    }

    /**
     * @param Money $offerMoney
     *
     * @throws Exception
     */
    private function setOfferMoney(Money $offerMoney)
    {
        $this->getTrancheOffers()->clear();

        $syndicatedMoney = $this->getProject()->getSyndicatedAmount();
        $remainderMoney  = clone $syndicatedMoney;

        foreach ($this->getProject()->getTranches() as $tranche) {
            $split          = $offerMoney->multiply($syndicatedMoney->divide($tranche->getMoney()));
            $remainderMoney = $remainderMoney->substract($split);

            $this->trancheOffers->add(
                new TrancheOffer($this, $tranche, $split, $this->addedBy)
            );
        }

        /** @var TrancheOffer $lastTrancheOffer */
        $lastTrancheOffer = $this->getTrancheOffers()->last();
        $lastTrancheOffer->getMoney()->add($remainderMoney);
    }
}
