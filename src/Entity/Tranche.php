<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\{Money, NullableLendingRate};
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\TrancheRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * Short explanations about loan facilities can be found at https://www.translegal.com/lesson/3263
 */
class Tranche
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const LOAN_TYPE_TERM_LOAN        = 'term_loan';
    public const LOAN_TYPE_REVOLVING_CREDIT = 'revolving_credit';

    public const REPAYMENT_TYPE_AMORTIZABLE = 'amortizable';
    public const REPAYMENT_TYPE_IN_FINE     = 'in_fine';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="tranches")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     *
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     */
    private $loanType;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     */
    private $repaymentType;

    /**
     * Duration in month. It is used to calculate the maturity date once the project is funded.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Range(min="1")
     * @Assert\NotBlank
     */
    private $duration;

    /**
     * The capital repayment periodicity in month.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Range(min="1")
     * @Assert\NotBlank
     */
    private $capitalPeriodicity;

    /**
     * The interest repayment periodicity in month.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Range(min="1")
     * @Assert\NotBlank
     */
    private $interestPeriodicity;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Money")
     */
    private $money;

    /**
     * @var NullableLendingRate
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableLendingRate")
     */
    private $rate;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     */
    private $expectedReleasingDate;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     */
    private $expectedStartingDate;

    /**
     * @var TrancheFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TrancheFee", mappedBy="tranche", cascade={"persist"}, orphanRemoval=true)
     */
    private $trancheFees;

    /**
     * @var Bids[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Bids", mappedBy="tranche")
     * @ORM\OrderBy({"added": "DESC"})
     */
    private $bids;

    /**
     * @var Loans[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\Loans", mappedBy="tranche")
     */
    private $loans;

    /**
     * @var TrancheAttribute[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="TrancheAttribute", mappedBy="tranche", cascade={"persist"}, orphanRemoval=true)
     */
    private $trancheAttributes;

    /**
     * Tranche constructor.
     */
    public function __construct()
    {
        $this->money       = new Money();
        $this->rate        = new NullableLendingRate();
        $this->trancheFees = new ArrayCollection();
        $this->bids        = new ArrayCollection();
        $this->loans       = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return Tranche
     */
    public function setProject(Project $project): Tranche
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Tranche
     */
    public function setName(string $name): Tranche
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }

    /**
     * @param Money $money
     *
     * @return Tranche
     */
    public function setMoney(Money $money): Tranche
    {
        $this->money = $money;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLoanType(): ?string
    {
        return $this->loanType;
    }

    /**
     * @param string $loanType
     *
     * @return Tranche
     */
    public function setLoanType(string $loanType): Tranche
    {
        $this->loanType = $loanType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRepaymentType(): ?string
    {
        return $this->repaymentType;
    }

    /**
     * @param string $repaymentType
     *
     * @return Tranche
     */
    public function setRepaymentType(string $repaymentType): Tranche
    {
        $this->repaymentType = $repaymentType;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     *
     * @return Tranche
     */
    public function setDuration(int $duration): Tranche
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCapitalPeriodicity(): ?int
    {
        return $this->capitalPeriodicity;
    }

    /**
     * @param int $capitalPeriodicity
     *
     * @return Tranche
     */
    public function setCapitalPeriodicity(int $capitalPeriodicity): Tranche
    {
        $this->capitalPeriodicity = $capitalPeriodicity;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getInterestPeriodicity(): ?int
    {
        return $this->interestPeriodicity;
    }

    /**
     * @param int $interestPeriodicity
     *
     * @return Tranche
     */
    public function setInterestPeriodicity(int $interestPeriodicity): Tranche
    {
        $this->interestPeriodicity = $interestPeriodicity;

        return $this;
    }

    /**
     * @return NullableLendingRate
     */
    public function getRate(): NullableLendingRate
    {
        return $this->rate;
    }

    /**
     * @param NullableLendingRate $rate
     *
     * @return Tranche
     */
    public function setRate(NullableLendingRate $rate): Tranche
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getExpectedReleasingDate(): ?DateTimeImmutable
    {
        return $this->expectedReleasingDate;
    }

    /**
     * @param DateTimeImmutable|null $expectedReleasingDate
     *
     * @return Tranche
     */
    public function setExpectedReleasingDate(?DateTimeImmutable $expectedReleasingDate): Tranche
    {
        $this->expectedReleasingDate = $expectedReleasingDate;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getExpectedStartingDate(): ?DateTimeImmutable
    {
        return $this->expectedStartingDate;
    }

    /**
     * @param DateTimeImmutable|null $expectedStartingDate
     *
     * @return Tranche
     */
    public function setExpectedStartingDate(?DateTimeImmutable $expectedStartingDate): Tranche
    {
        $this->expectedStartingDate = $expectedStartingDate;

        return $this;
    }

    /**
     * @param TrancheFee $trancheFee
     *
     * @return Tranche
     */
    public function addTrancheFee(TrancheFee $trancheFee): Tranche
    {
        $trancheFee->setTranche($this);

        if (false === $this->trancheFees->contains($trancheFee)) {
            $this->trancheFees->add($trancheFee);
        }

        return $this;
    }

    /**
     * @param TrancheFee $trancheFee
     *
     * @return Tranche
     */
    public function removeTrancheFee(TrancheFee $trancheFee): Tranche
    {
        if ($this->trancheFees->contains($trancheFee)) {
            $this->trancheFees->removeElement($trancheFee);
        }

        return $this;
    }

    /**
     * @return iterable|TrancheFee[]
     */
    public function getTrancheFees(): iterable
    {
        return $this->trancheFees;
    }

    /**
     * @param array|null     $status
     * @param Companies|null $lender
     *
     * @return Bids[]|ArrayCollection
     */
    public function getBids(?array $status = null, ?Companies $lender = null): iterable
    {
        $criteria = new Criteria();

        if (null !== $status) {
            $criteria->andWhere(Criteria::expr()->in('status', $status));
        }

        if (null !== $lender) {
            $criteria->andWhere(Criteria::expr()->eq('lender', $lender));
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
     * @return array
     */
    public static function getLoanTypes(): array
    {
        return self::getConstants('LOAN_TYPE_');
    }

    /**
     * @return array
     */
    public static function getRepaymentTypes(): array
    {
        return self::getConstants('REPAYMENT_TYPE_');
    }

    /**
     * @param string|null $name
     *
     * @return TrancheAttribute[]|ArrayCollection
     */
    public function getTrancheAttributes(?string $name = null): iterable
    {
        $criteria = new Criteria();
        if ($name) {
            $criteria->where(Criteria::expr()->eq('attribute.name', $name));
        }

        return $this->trancheAttributes->matching($criteria);
    }

    /**
     * @param TrancheAttribute $trancheAttribute
     *
     * @return Tranche
     */
    public function addTrancheAttribute(TrancheAttribute $trancheAttribute): Tranche
    {
        $trancheAttribute->setTranche($this);

        if (false === $this->trancheAttributes->contains($trancheAttribute) && false === empty($trancheAttribute->getAttribute()->getValue())) {
            $this->trancheAttributes->add($trancheAttribute);
        }

        return $this;
    }

    /**
     * @param TrancheAttribute $trancheAttribute
     *
     * @return Tranche
     */
    public function removeTrancheAttribute(TrancheAttribute $trancheAttribute): Tranche
    {
        $this->trancheAttributes->removeElement($trancheAttribute);

        return $this;
    }
}
