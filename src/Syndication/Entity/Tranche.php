<?php

declare(strict_types=1);

namespace Unilend\Syndication\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Constant\Tranche\CommissionType;
use Unilend\Core\Entity\Constant\Tranche\LoanType;
use Unilend\Core\Entity\Constant\Tranche\RepaymentType;
use Unilend\Core\Entity\Embeddable\LendingRate;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Interfaces\MoneyInterface;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Service\MoneyCalculator;
use Unilend\Core\Traits\ConstantsAwareTrait;

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
 * @ORM\Entity
 * @ORM\Table(name="syndication_tranche")
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Syndication\Entity\Versioned\VersionedTranche")
 *
 * Short explanations about loan facilities can be found at https://www.translegal.com/lesson/3263
 */
class Tranche
{
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use PublicizeIdentityTrait;

    public const UNSYNDICATED_FUNDER_TYPE_ARRANGER    = 'arranger';
    public const UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY = 'third_party';

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Syndication\Entity\Project", inversedBy="tranches")
     * @ORM\JoinColumn(name="id_project", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"tranche:create", "tranche:read"})
     */
    private Project $project;

    /**
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
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback={LoanType::class, "getConstList"})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private string $loanType;

    /**
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback={RepaymentType::class, "getConstList"})
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private string $repaymentType;

    /**
     * Duration in month. It is used to calculate the maturity date once the project is funded.
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
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?int $capitalPeriodicity;

    /**
     * The interest repayment periodicity in month.
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?int $interestPeriodicity;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
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
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\LendingRate")
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
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?DateTimeImmutable $expectedReleasingDate;

    /**
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private ?DateTimeImmutable $expectedStartingDate;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Choice(callback={CommissionType::class, "getConstList"})
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $commissionType = null;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Expression(
     *     "this.isCommissionRateValid()",
     *     message="Syndication.Tranche.commissionRate.expression"
     * )
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $commissionRate = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $comment;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private bool $syndicated;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Expression(
     *     expression="(!this.isSyndicated() && this.getUnsyndicatedFunderType()) || this.isSyndicated() && this.getUnsyndicatedFunderType() === null",
     *     message="Syndication.Tranche.unsyndicatedFunderType.expression"
     * )
     * @Assert\Choice(callback="getUnsyndicatedFunderTypes")
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $unsyndicatedFunderType;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Expression(
     *     expression="this.isThirdPartyFunderValid() === true",
     *     message="Syndication.Tranche.thirdPartyFunder.expression"
     * )
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private ?string $thirdPartyFunder;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\Regex(pattern="/#[0-9a-f]{3}([0-9a-f]{3})?/i", message="Syndication.Tranche.color.regex")
     * @Assert\NotBlank
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private string $color;

    /**
     * @var ProjectParticipationTranche[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Syndication\Entity\ProjectParticipationTranche", mappedBy="tranche", cascade={"persist"}, orphanRemoval=true, fetch="LAZY")
     *
     * @Groups({"tranche:read"})
     */
    private $projectParticipationTranches;

    /**
     * @throws \Exception
     */
    public function __construct(Project $project, Money $money, string $name, int $duration, string $repaymentType, string $loanType, string $color)
    {
        $this->money                        = $money;
        $this->rate                         = new LendingRate();
        $this->projectParticipationTranches = new ArrayCollection();
        $this->added                        = new DateTimeImmutable();
        $this->project                      = $project;
        $this->syndicated                   = true;
        $this->name                         = $name;
        $this->duration                     = $duration;
        $this->repaymentType                = $repaymentType;
        $this->loanType                     = $loanType;
        $this->color                        = $color;
        $this->unsyndicatedFunderType       = null;
        $this->thirdPartyFunder             = null;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): Tranche
    {
        $this->project = $project;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Tranche
    {
        $this->name = $name;

        return $this;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function setMoney(Money $money): Tranche
    {
        $this->money = $money;

        return $this;
    }

    public function getLoanType(): string
    {
        return $this->loanType;
    }

    public function setLoanType(string $loanType): Tranche
    {
        if (false === \in_array($loanType, LoanType::getChargeableLoanTypes())) {
            $this->setCommissionRate(null)
                ->setCommissionType(null)
            ;
        }

        $this->loanType = $loanType;

        return $this;
    }

    public function getRepaymentType(): string
    {
        return $this->repaymentType;
    }

    public function setRepaymentType(string $repaymentType): Tranche
    {
        $this->repaymentType = $repaymentType;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): Tranche
    {
        $this->duration = $duration;

        return $this;
    }

    public function getCapitalPeriodicity(): ?int
    {
        return $this->capitalPeriodicity;
    }

    public function setCapitalPeriodicity(int $capitalPeriodicity): Tranche
    {
        $this->capitalPeriodicity = $capitalPeriodicity;

        return $this;
    }

    public function getInterestPeriodicity(): ?int
    {
        return $this->interestPeriodicity;
    }

    public function setInterestPeriodicity(int $interestPeriodicity): Tranche
    {
        $this->interestPeriodicity = $interestPeriodicity;

        return $this;
    }

    public function getRate(): LendingRate
    {
        return $this->rate;
    }

    public function setRate(LendingRate $rate): Tranche
    {
        $this->rate = $rate;

        return $this;
    }

    public function getExpectedReleasingDate(): ?DateTimeImmutable
    {
        return $this->expectedReleasingDate;
    }

    public function setExpectedReleasingDate(?DateTimeImmutable $expectedReleasingDate): Tranche
    {
        $this->expectedReleasingDate = $expectedReleasingDate;

        return $this;
    }

    public function getExpectedStartingDate(): ?DateTimeImmutable
    {
        return $this->expectedStartingDate;
    }

    public function setExpectedStartingDate(?DateTimeImmutable $expectedStartingDate): Tranche
    {
        $this->expectedStartingDate = $expectedStartingDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): Tranche
    {
        $this->comment = $comment;

        return $this;
    }

    public function isSyndicated(): bool
    {
        return $this->syndicated;
    }

    public function setSyndicated(bool $syndicated): Tranche
    {
        $this->syndicated = $syndicated;

        if ($this->syndicated) {
            $this->unsyndicatedFunderType = null;
            $this->thirdPartyFunder       = null;
        }

        return $this;
    }

    public function getUnsyndicatedFunderType(): ?string
    {
        return $this->unsyndicatedFunderType;
    }

    public function setUnsyndicatedFunderType(?string $unsyndicatedFunderType): Tranche
    {
        $this->unsyndicatedFunderType = $unsyndicatedFunderType;

        if ($this->unsyndicatedFunderType !== static::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY) {
            $this->thirdPartyFunder = null;
        }

        return $this;
    }

    public function getThirdPartyFunder(): ?string
    {
        return $this->thirdPartyFunder;
    }

    public function setThirdPartyFunder(?string $thirdPartyFunder): Tranche
    {
        $this->thirdPartyFunder = $thirdPartyFunder ?: null;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): Tranche
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Method created because the expression would be too long to put on one line.
     */
    public function isThirdPartyFunderValid(): bool
    {
        return ($this->getUnsyndicatedFunderType() === static::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY && $this->getThirdPartyFunder())
            || ($this->getUnsyndicatedFunderType() !== static::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY && null === $this->getThirdPartyFunder());
    }

    /**
     * @return array
     */
    public static function getUnsyndicatedFunderTypes()
    {
        return static::getConstants('UNSYNDICATED_FUNDER_TYPE_');
    }

    public function getCommissionType(): ?string
    {
        return $this->commissionType;
    }

    public function setCommissionType(?string $commissionType): Tranche
    {
        $this->commissionType = $commissionType ?: null;

        if (null === $this->commissionType) {
            $this->commissionRate = null;
        }

        return $this;
    }

    public function getCommissionRate(): ?string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(?string $commissionRate): Tranche
    {
        $this->commissionRate = '' === $commissionRate ? null : $commissionRate;

        return $this;
    }

    /**
     * Used in an expression constraints.
     */
    public function isCommissionRateValid(): bool
    {
        return (null === $this->getCommissionType() && null === $this->getCommissionRate())
            || (
                ($this->getCommissionRate() || '0' === $this->getCommissionRate())
                && \in_array($this->getCommissionType(), CommissionType::getConstList(), true)
            );
    }

    /**
     * @return ProjectParticipationTranche[]|Collection
     */
    public function getProjectParticipationTranches(): Collection
    {
        return $this->projectParticipationTranches;
    }

    public function getTotalAllocationMoney(): MoneyInterface
    {
        $totalAllocation = new NullableMoney();

        foreach ($this->projectParticipationTranches as $projectParticipationTranche) {
            $totalAllocation = MoneyCalculator::add($totalAllocation, $projectParticipationTranche->getAllocation()->getMoney());
        }

        return $totalAllocation;
    }

    /**
     * @Assert\Callback
     */
    public function validateCurrencyConsistency(ExecutionContextInterface $context): void
    {
        $globalFundingMoney = $this->getProject()->getGlobalFundingMoney();

        if (MoneyCalculator::isDifferentCurrency($this->getMoney(), $globalFundingMoney)) {
            $context->buildViolation('Core.Money.currency.inconsistent')
                ->atPath('money')
                ->addViolation()
            ;
        }
    }

    /**
     * @return Tranche
     */
    public function addProjectParticipationTranche(ProjectParticipationTranche $projectParticipationTranche)
    {
        if (false === $this->hasProjectParticipationTranche($projectParticipationTranche)) {
            $this->projectParticipationTranches->add($projectParticipationTranche);
        }

        $projectParticipation = $projectParticipationTranche->getProjectParticipation();

        if (false === $projectParticipation->hasProjectParticipationTranche($projectParticipationTranche)) {
            $projectParticipation->addProjectParticipationTranche($projectParticipationTranche);
        }

        return $this;
    }

    public function hasProjectParticipationTranche(ProjectParticipationTranche $projectParticipationTranche): bool
    {
        return isset($this->projectParticipationTranches[$projectParticipationTranche->getProjectParticipation()->getId()]);
    }
}
