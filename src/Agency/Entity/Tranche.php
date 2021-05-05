<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Constant\Tranche\CommissionType;
use Unilend\Core\Entity\Constant\Tranche\LoanType;
use Unilend\Core\Entity\Constant\Tranche\RepaymentType;
use Unilend\Core\Entity\Embeddable\LendingRate;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "timestampable:read",
 *             "agency:tranche:read",
 *             "money:read",
 *             "nullableMoney:read",
 *             "lendingRate:read"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:tranche:create",
 *                     "agency:tranche:write",
 *                     "money:write",
 *                     "nullableMoney:write",
 *                     "lendingRate:write",
 *                     "agency:borrowerTrancheShare:write"
 *                 }
 *             },
 *             "security_post_denormalize": "is_granted('create', object)",
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": NotFoundAction::class,
 *             "read": false,
 *             "output": false,
 *         },
 *         "patch": {
 *             "denormalization_context": {
 *                 "groups": {
 *                     "agency:tranche:update",
 *                     "agency:tranche:write",
 *                     "money:write", "nullableMoney:write",
 *                     "lendingRate:write",
 *                     "agency:borrowerTrancheShare:write"
 *                 }
 *             },
 *             "security": "is_granted('edit', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)",
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="agency_tranche")
 *
 * @ApiResource(
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={}
 * )
 *
 * @ApiFilter(
 *     filterClass=GroupFilter::class,
 *     arguments={
 *         "whitelist": {
 *             "agency:borrowerTrancheShare:read",
 *             "agency:participationTrancheAllocation:read"
 *         }
 *     }
 * )
 */
class Tranche
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="tranches")
     * @ORM\JoinColumn(name="id_project", onDelete="CASCADE")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:tranche:read", "agency:tranche:create"})
     *
     * @ApiProperty(readableLink=false)
     */
    private Project $project;

    /**
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private string $name;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private bool $syndicated;

    /**
     * @ORM\Column(length=255, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Length(max="255")
     * @Assert\Expression(expression="(!this.isSyndicated() && value) || !value", message="Agency.Tranche.thirdPartySyndicate.invalid")
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private ?string $thirdPartySyndicate;

    /**
     * @ORM\Column(length=30, nullable=true)
     *
     * @Assert\Length(max="30")
     * @Assert\Regex(pattern="/#[0-9a-f]{3}([0-9a-f]{3})?/i", message="Syndication.Tranche.color.regex")
     * @Assert\NotBlank
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private string $color;

    /**
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback={LoanType::class, "getConstList"})
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private string $loanType;

    /**
     * @ORM\Column(length=30)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback={RepaymentType::class, "getConstList"})
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
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
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private int $duration;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private Money $money;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private NullableMoney $draw;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\LendingRate")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private LendingRate $rate;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Choice(callback={CommissionType::class, "getConstList"})
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private ?string $commissionType;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private ?string $commissionRate;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private ?string $comment;

    /**
     * @var BorrowerTrancheShare[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\BorrowerTrancheShare", mappedBy="tranche", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     *
     * @Assert\All({
     *     @Assert\Expression("value.getTranche() === this")
     * })
     */
    private Collection $borrowerShares;

    /**
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\GreaterThanOrEqual(value="today")
     *
     * @Groups({"agency:tranche:read", "agency:tranche:write"})
     */
    private ?DateTimeImmutable $validityDate;

    /**
     * @var ParticipationTrancheAllocation[]|Collection
     *
     * @ORM\OneToMany(
     *     targetEntity=ParticipationTrancheAllocation::class,
     *     cascade={"persist", "remove"},
     *     mappedBy="tranche",
     *     orphanRemoval=true
     * )
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\Expression("value.getTranche() === this")
     * })
     *
     * @Groups({"agency:tranche:read"})
     */
    private Collection $allocations;

    public function __construct(
        Project $project,
        string $name,
        bool $syndicated,
        string $color,
        string $loanType,
        string $repaymentType,
        int $duration,
        Money $money,
        LendingRate $rate
    ) {
        $this->project             = $project;
        $this->name                = $name;
        $this->syndicated          = $syndicated;
        $this->thirdPartySyndicate = null;
        $this->color               = $color;
        $this->loanType            = $loanType;
        $this->repaymentType       = $repaymentType;
        $this->duration            = $duration;
        $this->money               = $money;
        $this->rate                = $rate;
        $this->commissionType      = null;
        $this->commissionRate      = null;
        $this->comment             = null;
        $this->draw                = new NullableMoney();
        $this->borrowerShares      = new ArrayCollection();
        $this->validityDate        = null;
        $this->allocations         = new ArrayCollection();
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

    public function isSyndicated(): bool
    {
        return $this->syndicated;
    }

    public function setSyndicated(bool $syndicated): Tranche
    {
        $this->syndicated = $syndicated;

        if (false === $syndicated) {
            $this->allocations = new ArrayCollection();
        }

        return $this;
    }

    public function getThirdPartySyndicate(): ?string
    {
        return $this->thirdPartySyndicate;
    }

    public function setThirdPartySyndicate(?string $thirdPartySyndicate): Tranche
    {
        $this->thirdPartySyndicate = $thirdPartySyndicate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArrangerFullyFinanced()
    {
        return false === $this->isSyndicated() && null === $this->thirdPartySyndicate;
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

    public function getLoanType(): string
    {
        return $this->loanType;
    }

    public function setLoanType(string $loanType): Tranche
    {
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

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function setMoney(Money $money): Tranche
    {
        $this->money = $money;

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

    public function getCommissionType(): ?string
    {
        return $this->commissionType;
    }

    public function setCommissionType(?string $commissionType): Tranche
    {
        $this->commissionType = $commissionType;

        return $this;
    }

    public function getCommissionRate(): ?string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(?string $commissionRate): Tranche
    {
        $this->commissionRate = $commissionRate;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): Tranche
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return iterable|BorrowerTrancheShare[]
     */
    public function getBorrowerShares(): iterable
    {
        return $this->borrowerShares;
    }

    /**
     * @param iterable|BorrowerTrancheShare[] $borrowerShares
     *
     * @return Tranche
     */
    public function setBorrowerShares(iterable $borrowerShares)
    {
        // This convoluted way of setting the borrowerShares is needed because orphanRemoval doesn't handle
        $this->borrowerShares->clear();

        foreach ($borrowerShares as $borrowerShare) {
            $this->addBorrowerShare($borrowerShare);
        }

        return $this;
    }

    /**
     * @return ?DateTimeImmutable
     */
    public function getValidityDate(): ?DateTimeImmutable
    {
        return $this->validityDate;
    }

    /**
     * @param ?DateTimeImmutable $validityDate
     */
    public function setValidityDate(?DateTimeImmutable $validityDate): Tranche
    {
        $this->validityDate = $validityDate;

        return $this;
    }

    public function getDraw(): NullableMoney
    {
        return $this->draw;
    }

    public function setDraw(NullableMoney $draw): Tranche
    {
        $this->draw = $draw;

        return $this;
    }

    /**
     * @return iterable|ParticipationTrancheAllocation[]
     */
    public function getAllocations()
    {
        return $this->allocations;
    }

    public function addBorrowerShare(BorrowerTrancheShare $borrowerTrancheShare): Tranche
    {
        if (false === $this->borrowerShares->contains($borrowerTrancheShare)) {
            $this->borrowerShares->add($borrowerTrancheShare);
        }

        return $this;
    }

    public function removeBorrowerShare(BorrowerTrancheShare $borrowerTrancheShare): Tranche
    {
        $this->borrowerShares->removeElement($borrowerTrancheShare);

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validateCommission(ExecutionContextInterface $context)
    {
        if (
            ($this->getCommissionRate() || '0' === $this->getCommissionRate() || $this->getCommissionType())
            && false === \in_array($this->getLoanType(), LoanType::getChargeableLoanTypes(), true)
        ) {
            $context->buildViolation('Agency.Tranche.commission.invalidLoanType')
                ->addViolation()
            ;
        }

        if (($this->getCommissionRate() || '0' === $this->getCommissionRate()) xor $this->getCommissionType()) {
            $context->buildViolation('Agency.Tranche.commission.incomplete')
                ->atPath($this->getCommissionType() ? 'commissionRate' : 'commissionType')
                ->addViolation()
            ;
        }
    }

    public function addAllocation(ParticipationTrancheAllocation $participationTrancheAllocation): Tranche
    {
        if (
            false === $this->allocations->exists(
                fn ($key, ParticipationTrancheAllocation $item) => $item->getParticipation() === $participationTrancheAllocation->getParticipation()
            )
        ) {
            $this->allocations->add($participationTrancheAllocation);
            $participationTrancheAllocation->getParticipation()->addAllocation($participationTrancheAllocation);
        }

        return $this;
    }

    public function removeAllocation(ParticipationTrancheAllocation $participationTrancheAllocation): Tranche
    {
        $this->allocations->removeElement($participationTrancheAllocation);

        return $this;
    }
}
