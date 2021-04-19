<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Interfaces\StatusInterface;
use Unilend\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Validator\Constraints\PreviousValue;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"creditGuaranty:program:read", "creditGuaranty:programStatus:read", "timestampable:read", "money:read", "nullableMoney:read"}},
 *     denormalizationContext={"groups": {"creditGuaranty:program:write", "money:write", "nullableMoney:write"}},
 *     itemOperations={
 *         "get",
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"}
 *     },
 *     collectionOperations={
 *         "post": {"security_post_denormalize": "is_granted('create', object)"},
 *         "get"
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"name"}, message="CreditGuaranty.Program.name.unique")
 */
class Program implements TraceableStatusAwareInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;

    public const COMPANY_GROUP_TAG_CORPORATE   = 'corporate';
    public const COMPANY_GROUP_TAG_AGRICULTURE = 'agriculture';

    /**
     * @ORM\Column(length=100, unique=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private string $name;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $description;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\CompanyGroupTag")
     * @ORM\JoinColumn(name="id_company_group_tag", nullable=false)
     *
     * @Assert\Expression(
     *     "this.isCompanyGroupTagValid()",
     *     message="CreditGuaranty.Program.companyGroupTag.invalid"
     * )
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private CompanyGroupTag $companyGroupTag;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.99")
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isInDraft()", message="CreditGuaranty.Program.cappedAt.draft"),
     *     @Assert\Sequentially({
     *
     *         @PreviousValue\ScalarNotAssignable(message="CreditGuaranty.Program.cappedAt.notChangeable"),
     *         @PreviousValue\ScalarNotResettable(message="CreditGuaranty.Program.cappedAt.notChangeable"),
     *         @PreviousValue\NumericGreaterThanOrEqual(message="CreditGuaranty.Program.cappedAt.greater")
     *     })
     * })
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $cappedAt;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\Valid
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("this.isInDraft()", message="CreditGuaranty.Program.funds.draft"),
     *
     *     @PreviousValue\MoneyGreaterThanOrEqual(message="CreditGuaranty.Program.funds.greater")
     * })
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private Money $funds;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?DateTimeImmutable $distributionDeadline;

    /**
     * @ORM\Column(type="json", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Count(max="10")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Length(max=200)
     * })
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?array $distributionProcess;

    /**
     * Duration in month.
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\GreaterThanOrEqual(1)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?int $guarantyDuration;

    /**
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $guarantyCoverage;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private NullableMoney $guarantyCost;

    /**
     * @var ProgramStatus|null
     *
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramStatus", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_status", unique=true, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @MaxDepth(1)
     *
     * @Groups({"creditGuaranty:program:read"})
     */
    private ?ProgramStatus $currentStatus;

    /**
     * @var Collection|ProgramStatus[]
     *
     * @Assert\Valid
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramStatus", mappedBy="program", orphanRemoval=true, cascade={"persist"}, fetch="EAGER")
     */
    private Collection $statuses;

    /**
     * @ORM\Column(length=60, nullable=true)
     *
     * @Assert\Choice(callback={CARatingType::class, "getConstList"})
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?string $ratingType = null;

    /**
     * @var Collection|ProgramGradeAllocation[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramGradeAllocation", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private Collection $programGradeAllocations;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     * The max of a signed "smallint" is 32767
     * @Assert\Range(min="1", max="32767")
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:program:read", "creditGuaranty:program:write"})
     */
    private ?int $reservationDuration;

    // Subresource

    /**
     * @var Collection|ProgramBorrowerTypeAllocation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private Collection $programBorrowerTypeAllocations;

    /**
     * @var Collection|ProgramChoiceOption[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private Collection $programChoiceOptions;

    /**
     * @var Collection|ProgramContact[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramContact", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private Collection $programContacts;

    /**
     * @var Collection|ProgramEligibility[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibility", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private Collection $programEligibilities;

    /**
     * @var Collection|Participation[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\Participation", mappedBy="program", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private Collection $participations;

    /**
     * @param string          $name
     * @param CompanyGroupTag $companyGroupTag
     * @param Money           $funds
     * @param Staff           $addedBy
     */
    public function __construct(string $name, CompanyGroupTag $companyGroupTag, Money $funds, Staff $addedBy)
    {
        $this->name                    = $name;
        $this->companyGroupTag         = $companyGroupTag;
        $this->funds                   = $funds;
        $this->addedBy                 = $addedBy;
        $this->statuses                = new ArrayCollection();
        $this->guarantyCost            = new NullableMoney();
        $this->added                   = new DateTimeImmutable();
        $this->programGradeAllocations = new ArrayCollection();
        $this->setCurrentStatus(new ProgramStatus($this, ProgramStatus::STATUS_DRAFT, $addedBy));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return Program
     */
    public function setDescription(?string $description): Program
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return CompanyGroupTag
     */
    public function getCompanyGroupTag(): CompanyGroupTag
    {
        return $this->companyGroupTag;
    }

    /**
     * @return string|null
     */
    public function getCappedAt(): ?string
    {
        return $this->cappedAt;
    }

    /**
     * @param string|null $cappedAt
     *
     * @return Program
     */
    public function setCappedAt(?string $cappedAt): Program
    {
        $this->cappedAt = $cappedAt;

        return $this;
    }

    /**
     * @return Money
     */
    public function getFunds(): Money
    {
        return $this->funds;
    }

    /**
     * @param Money $funds
     *
     * @return Program
     */
    public function setFunds(Money $funds): Program
    {
        $this->funds = $funds;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getDistributionDeadline(): ?DateTimeImmutable
    {
        return $this->distributionDeadline;
    }

    /**
     * @param DateTimeImmutable|null $distributionDeadline
     *
     * @return Program
     */
    public function setDistributionDeadline(?DateTimeImmutable $distributionDeadline): Program
    {
        $this->distributionDeadline = $distributionDeadline;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getDistributionProcess(): ?array
    {
        return $this->distributionProcess;
    }

    /**
     * @param array|null $distributionProcess
     *
     * @return Program
     */
    public function setDistributionProcess(?array $distributionProcess): Program
    {
        $this->distributionProcess = $distributionProcess;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getGuarantyDuration(): ?int
    {
        return $this->guarantyDuration;
    }

    /**
     * @param int|null $guarantyDuration
     *
     * @return Program
     */
    public function setGuarantyDuration(?int $guarantyDuration): Program
    {
        $this->guarantyDuration = $guarantyDuration;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGuarantyCoverage(): ?string
    {
        return $this->guarantyCoverage;
    }

    /**
     * @param string|null $guarantyCoverage
     *
     * @return Program
     */
    public function setGuarantyCoverage(?string $guarantyCoverage): Program
    {
        $this->guarantyCoverage = $guarantyCoverage;

        return $this;
    }

    /**
     * @return NullableMoney
     */
    public function getGuarantyCost(): NullableMoney
    {
        return $this->guarantyCost;
    }

    /**
     * @param NullableMoney $guarantyCost
     *
     * @return Program
     */
    public function setGuarantyCost(NullableMoney $guarantyCost): Program
    {
        $this->guarantyCost = $guarantyCost;

        return $this;
    }

    /**
     * @return Collection|ProgramStatus[]
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    /**
     * @return StatusInterface
     */
    public function getCurrentStatus(): StatusInterface
    {
        return $this->currentStatus;
    }

    /**
     * @param StatusInterface|ProgramStatus $status
     *
     * @return $this
     */
    public function setCurrentStatus(StatusInterface $status): Program
    {
        $this->currentStatus = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRatingType(): ?string
    {
        return $this->ratingType;
    }

    /**
     * @param string|null $ratingType
     *
     * @return $this
     */
    public function setRatingType(?string $ratingType): Program
    {
        if ($ratingType !== $this->ratingType) {
            $this->programGradeAllocations->clear();
        }

        $this->ratingType = $ratingType;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getReservationDuration(): ?int
    {
        return $this->reservationDuration;
    }

    /**
     * @param int|null $reservationDuration
     *
     * @return Program
     */
    public function setReservationDuration(?int $reservationDuration): Program
    {
        $this->reservationDuration = $reservationDuration;

        return $this;
    }

    /**
     * Used in an expression constraints.
     *
     * @return bool
     */
    public function isCompanyGroupTagValid(): bool
    {
        return \in_array($this->getCompanyGroupTag()->getCode(), [self::COMPANY_GROUP_TAG_CORPORATE, self::COMPANY_GROUP_TAG_AGRICULTURE], true);
    }

    /**
     * @return bool
     */
    public function isInDraft(): bool
    {
        return ProgramStatus::STATUS_DRAFT === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @return bool
     */
    public function isPaused(): bool
    {
        return ProgramStatus::STATUS_PAUSED === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @return bool
     */
    public function isDistributed(): bool
    {
        return ProgramStatus::STATUS_DISTRIBUTED === $this->getCurrentStatus()->getStatus();
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return ProgramStatus::STATUS_CANCELLED === $this->getCurrentStatus()->getStatus();
    }
}
