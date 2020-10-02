<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\{LendingRate, Money, NullableMoney};
use Unilend\Entity\Interfaces\MoneyInterface;
use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Service\MoneyCalculator;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"tranche:read", "fee:read", "lendingRate:read", "money:read"}},
 *     denormalizationContext={"groups": {"tranche:write", "fee:write", "lendingRate:write", "money:write"}},
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('edit', object.getProject())",
 *             "denormalization_context": {
 *                 "groups": {"tranche:create", "tranche:write", "fee:write", "lendingRate:write", "money:write"}
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "delete": {"security": "is_granted('edit', object.getProject())"},
 *         "get": {"security": "is_granted('view', object.getProject())"},
 *         "put": {"security_post_denormalize": "is_granted('edit', previous_object.getProject())"},
 *         "patch": {"security_post_denormalize": "is_granted('edit', previous_object.getProject())"}
 *     }
 * )
 *
 * @ORM\Entity(repositoryClass="Unilend\Repository\TrancheRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedTranche")
 *
 * Short explanations about loan facilities can be found at https://www.translegal.com/lesson/3263
 */
class Tranche
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use PublicizeIdentityTrait;

    public const AMORTIZABLE_REPAYMENT_TYPE = [
        self::REPAYMENT_TYPE_CONSTANT_CAPITAL,
        self::REPAYMENT_TYPE_FIXED,
    ];

    protected const LOAN_TYPE_TERM_LOAN            = 'term_loan';
    protected const LOAN_TYPE_SHORT_TERM           = 'short_term';
    protected const LOAN_TYPE_REVOLVING_CREDIT     = 'revolving_credit';
    protected const LOAN_TYPE_STAND_BY             = 'stand_by';
    protected const LOAN_TYPE_SIGNATURE_COMMITMENT = 'signature_commitment';

    protected const CHARGEABLE_LOAN_TYPE = [
        self::LOAN_TYPE_SHORT_TERM,
        self::LOAN_TYPE_REVOLVING_CREDIT,
        self::LOAN_TYPE_STAND_BY,
    ];

    protected const REPAYMENT_TYPE_ATYPICAL         = 'atypical';
    protected const REPAYMENT_TYPE_IN_FINE          = 'in_fine';
    protected const REPAYMENT_TYPE_CONSTANT_CAPITAL = 'constant_capital';
    protected const REPAYMENT_TYPE_FIXED            = 'repayment_fixed';

    public const UNSYNDICATED_FUNDER_TYPE_ARRANGER    = 'arranger';
    public const UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY = 'third_party';

    protected const COMMISSION_TYPE_NON_UTILISATION = 'non_utilisation';
    protected const COMMISSION_TYPE_COMMITMENT      = 'commitment';

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="tranches")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"tranche:create", "tranche:read"})
     */
    private Project $project;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     *
     * @Assert\NotBlank
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private string $name;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getLoanTypes")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private string $loanType;

    /**
     * @var string
     *
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getRepaymentTypes")
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private string $repaymentType;

    /**
     * Duration in month. It is used to calculate the maturity date once the project is funded.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\GreaterThanOrEqual(1)
     * @Assert\NotBlank
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private int $duration;

    /**
     * The capital repayment periodicity in month.
     *
     * @var int|null
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?int $capitalPeriodicity;

    /**
     * The interest repayment periodicity in month.
     *
     * @var int|null
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?int $interestPeriodicity;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Money")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:read", "tranche:write"})
     */
    private Money $money;

    /**
     * @var LendingRate
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\LendingRate")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:read", "tranche:write"})
     */
    private LendingRate $rate;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?DateTimeImmutable $expectedReleasingDate;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?DateTimeImmutable $expectedStartingDate;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Choice(callback="getCommissionTypes")
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $commissionType = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Expression(
     *     "this.isCommissionRateValid()",
     *     message="Tranche.commissionRate.expression"
     * )
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $commissionRate = null;

    /**
     * @var Collection|TrancheAttribute[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TrancheAttribute", mappedBy="tranche", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $trancheAttributes;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $comment;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private bool $syndicated;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Expression(
     *     expression="(!this.isSyndicated() && this.getUnsyndicatedFunderType()) || this.isSyndicated() && this.getUnsyndicatedFunderType() === null",
     *     message="Tranche.unsyndicatedFunderType.expression"
     * )
     * @Assert\Choice(callback="getUnsyndicatedFunderTypes")
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $unsyndicatedFunderType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Expression(
     *     expression="this.isThirdPartyFunderValid() === true",
     *     message="Tranche.thirdPartyFunder.expression"
     * )
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $thirdPartyFunder;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\Regex(pattern="/#[0-9a-f]{3}([0-9a-f]{3})?/i", message="Tranche.color.regex")
     * @Assert\NotBlank
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private string $color;

    /**
     * @var ProjectParticipationTranche[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectParticipationTranche", mappedBy="tranche", cascade={"persist"}, orphanRemoval=true, fetch="LAZY")
     *
     * @Groups({"tranche:read"})
     */
    private $projectParticipationTranches;

    /**
     * @param Project $project
     * @param Money   $money
     * @param string  $name
     * @param int     $duration
     * @param string  $repaymentType
     * @param string  $loanType
     * @param string  $color
     *
     * @throws \Exception
     */
    public function __construct(Project $project, Money $money, string $name, int $duration, string $repaymentType, string $loanType, string $color)
    {
        $this->money                        = $money;
        $this->rate                         = new LendingRate();
        $this->trancheAttributes            = new ArrayCollection();
        $this->projectParticipationTranches = new ArrayCollection();
        $this->added                        = new DateTimeImmutable();
        $this->project                      = $project;
        $this->syndicated                   = true;
        $this->name                         = $name;
        $this->duration                     = $duration;
        $this->repaymentType                = $repaymentType;
        $this->loanType                     = $loanType;
        $this->color                        = $color;
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
     * @return string
     */
    public function getName(): string
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
     * @return string
     */
    public function getLoanType(): string
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
        if (false === \in_array($loanType, self::CHARGEABLE_LOAN_TYPE)) {
            $this->setCommissionRate(null)
                ->setCommissionType(null)
            ;
        }

        $this->loanType = $loanType;

        return $this;
    }

    /**
     * @return string
     */
    public function getRepaymentType(): string
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
     * @return int
     */
    public function getDuration(): int
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
     * @return LendingRate
     */
    public function getRate(): LendingRate
    {
        return $this->rate;
    }

    /**
     * @param LendingRate $rate
     *
     * @return Tranche
     */
    public function setRate(LendingRate $rate): Tranche
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

    /**
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     *
     * @return Tranche
     */
    public function setComment(?string $comment): Tranche
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSyndicated(): bool
    {
        return $this->syndicated;
    }

    /**
     * @param bool $syndicated
     *
     * @return Tranche
     */
    public function setSyndicated(bool $syndicated): Tranche
    {
        $this->syndicated = $syndicated;

        if ($this->syndicated) {
            $this->unsyndicatedFunderType = null;
            $this->thirdPartyFunder       = null;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUnsyndicatedFunderType(): ?string
    {
        return $this->unsyndicatedFunderType;
    }

    /**
     * @param string|null $unsyndicatedFunderType
     *
     * @return Tranche
     */
    public function setUnsyndicatedFunderType(?string $unsyndicatedFunderType): Tranche
    {
        $this->unsyndicatedFunderType = $unsyndicatedFunderType;

        if ($this->unsyndicatedFunderType !== static::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY) {
            $this->thirdPartyFunder = null;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getThirdPartyFunder(): ?string
    {
        return $this->thirdPartyFunder;
    }

    /**
     * @param string|null $thirdPartyFunder
     *
     * @return Tranche
     */
    public function setThirdPartyFunder(?string $thirdPartyFunder): Tranche
    {
        $this->thirdPartyFunder = $thirdPartyFunder ?: null;

        return $this;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string $color
     *
     * @return Tranche
     */
    public function setColor(string $color): Tranche
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Method created because the expression would be too long to put on one line.
     *
     * @return bool
     */
    public function isThirdPartyFunderValid(): bool
    {
        return ($this->getUnsyndicatedFunderType() === static::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY && $this->getThirdPartyFunder())
            || ($this->getUnsyndicatedFunderType() !== static::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY && null === $this->getThirdPartyFunder());
    }

    /**
     * @return array
     */
    public static function getCommissionTypes()
    {
        return static::getConstants('COMMISSION_TYPE_');
    }

    /**
     * @return array
     */
    public static function getUnsyndicatedFunderTypes()
    {
        return static::getConstants('UNSYNDICATED_FUNDER_TYPE_');
    }

    /**
     * @return string|null
     */
    public function getCommissionType(): ?string
    {
        return $this->commissionType;
    }

    /**
     * @param string|null $commissionType
     *
     * @return Tranche
     */
    public function setCommissionType(?string $commissionType): Tranche
    {
        $this->commissionType = $commissionType;

        if (null === $this->commissionType) {
            $this->commissionRate = null;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCommissionRate(): ?string
    {
        return $this->commissionRate;
    }

    /**
     * @param string|null $commissionRate
     *
     * @return Tranche
     */
    public function setCommissionRate(?string $commissionRate): Tranche
    {
        $this->commissionRate = '' === $commissionRate ? null : $commissionRate;

        return $this;
    }

    /**
     * Used in an expression constraints.
     *
     * @return bool
     */
    public function isCommissionRateValid(): bool
    {
        return (null === $this->getCommissionType() && null === $this->getCommissionRate())
            || (
                ($this->getCommissionRate() || '0' === $this->getCommissionRate())
                && \in_array($this->getLoanType(), self::CHARGEABLE_LOAN_TYPE, true)
                && \in_array($this->getCommissionType(), self::getCommissionTypes(), true)
            );
    }

    /**
     * @return ProjectParticipationTranche[]|Collection
     */
    public function getProjectParticipationTranches(): Collection
    {
        return $this->projectParticipationTranches;
    }

    /**
     * @return MoneyInterface
     */
    public function getTotalAllocationMoney(): MoneyInterface
    {
        $totalAllocation = new NullableMoney();

        foreach ($this->projectParticipationTranches as $projectParticipationTranche) {
            $totalAllocation = MoneyCalculator::add($totalAllocation, $projectParticipationTranche->getAllocation()->getMoney());
        }

        return $totalAllocation;
    }
}
