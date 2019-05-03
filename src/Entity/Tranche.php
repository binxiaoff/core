<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\{Money, NullableLendingRate};
use Unilend\Entity\Traits\{ConstantsAwareTrait, TimestampableTrait};

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\TrancheRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Tranche
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const REPAYMENT_TYPE_AMORTIZING_FIXED_PAYMENT = 'amortizing_fixed_payment';
    public const REPAYMENT_TYPE_AMORTIZING_FIXED_CAPITAL = 'amortizing_fixed_capital';
    public const REPAYMENT_TYPE_NON_AMORTIZING_IN_FINE   = 'non_amortizing_in_fine';
    public const REPAYMENT_TYPE_REVOLVING_CREDIT         = 'revolving_credit';

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
    private $repaymentType;

    /**
     * Duration in month. It is used to calculate the maturity date once the project is funded.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Range(min="1", max="99")
     */
    private $duration;

    /**
     * The capital repayment periodicity in month.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Range(min="1", max="99")
     */
    private $capitalPeriodicity;

    /**
     * The interest repayment periodicity in month.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Range(min="1", max="99")
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
     * @ORM\Column(type="date_immutable")
     *
     * @Assert\Date
     */
    private $expectedReleasingDate;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable")
     *
     * @Assert\Date
     */
    private $expectedStartingDate;

    /**
     * @var TranchePercentFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TranchePercentFee", mappedBy="tranche", cascade={"persist"}, orphanRemoval=true)
     */
    private $tranchePercentFees;

    /**
     * Tranche constructor.
     */
    public function __construct()
    {
        $this->money              = new Money();
        $this->rate               = new NullableLendingRate();
        $this->tranchePercentFees = new ArrayCollection();
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
    public function setName(string $name): self
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
    public function setMoney(Money $money): self
    {
        $this->money = $money;

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
     * @param DateTimeImmutable $expectedReleasingDate
     *
     * @return Tranche
     */
    public function setExpectedReleasingDate(DateTimeImmutable $expectedReleasingDate): Tranche
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
     * @param DateTimeImmutable $expectedStartingDate
     *
     * @return Tranche
     */
    public function setExpectedStartingDate(DateTimeImmutable $expectedStartingDate): Tranche
    {
        $this->expectedStartingDate = $expectedStartingDate;

        return $this;
    }

    /**
     * @param TranchePercentFee $tranchePercentFee
     *
     * @return Tranche
     */
    public function addTranchePercentFee(TranchePercentFee $tranchePercentFee): Tranche
    {
        $tranchePercentFee->setTranche($this);

        if (false === $this->tranchePercentFees->contains($tranchePercentFee)) {
            $this->tranchePercentFees->add($tranchePercentFee);
        }

        return $this;
    }

    /**
     * @param TranchePercentFee $tranchePercentFee
     *
     * @return Tranche
     */
    public function removeTranchePercentFee(TranchePercentFee $tranchePercentFee): Tranche
    {
        if ($this->tranchePercentFees->contains($tranchePercentFee)) {
            $this->tranchePercentFees->removeElement($tranchePercentFee);
        }

        return $this;
    }

    /**
     * @return iterable|TranchePercentFee[]
     */
    public function getTranchePercentFees(): iterable
    {
        return $this->tranchePercentFees;
    }

    /**
     * @return array
     */
    public static function getRepaymentTypes(): array
    {
        return self::getConstants('REPAYMENT_TYPE_');
    }
}
