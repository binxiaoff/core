<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\{Money, NullableLendingRate};
use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {"tranche:read", "trancheFee:read", "fee:read", "lendingRate:read", "money:read"}},
 *     denormalizationContext={"groups": {"tranche:write", "trancheFee:write", "fee:write", "lendingRate:write", "money:write"}},
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('edit', object.getProject())",
 *             "denormalization_context": {
 *                 "groups": {"tranche:create", "tranche:write", "trancheFee:write", "fee:write", "lendingRate:write", "money:write"}
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "delete": {"security": "is_granted('edit', object.getProject())"},
 *         "get": {"security": "is_granted('view', object.getProject())"},
 *         "put": {"security_post_denormalize": "is_granted('edit', previous_object.getProject())"}
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

    public const LOAN_TYPE_TERM_LOAN            = 'term_loan';
    public const LOAN_TYPE_SHORT_TERM           = 'short_term';
    public const LOAN_TYPE_REVOLVING_CREDIT     = 'revolving_credit';
    public const LOAN_TYPE_STAND_BY             = 'stand_by';
    public const LOAN_TYPE_SIGNATURE_COMMITMENT = 'signature_commitment';

    public const REPAYMENT_TYPE_ATYPICAL         = 'atypical';
    public const REPAYMENT_TYPE_IN_FINE          = 'in_fine';
    public const REPAYMENT_TYPE_CONSTANT_CAPITAL = 'constant_capital';
    public const REPAYMENT_TYPE_FIXED            = 'repayment_fixed';

    public const REPAYMENT_TYPE_AMORTIZABLE = [
        self::REPAYMENT_TYPE_CONSTANT_CAPITAL,
        self::REPAYMENT_TYPE_FIXED,
    ];

    public const UNSYNDICATED_FUNDER_TYPE_ARRANGER    = 'arranger';
    public const UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY = 'third_party';

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project", inversedBy="tranches")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     *
     * @Groups({"tranche:create", "tranche:read"})
     */
    private $project;

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
    private $name;

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
    private $loanType;

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
    private $repaymentType;

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
    private $duration;

    /**
     * The capital repayment periodicity in month.
     *
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $capitalPeriodicity;

    /**
     * The interest repayment periodicity in month.
     *
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Gedmo\Versioned
     */
    private $interestPeriodicity;

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
    private $money;

    /**
     * @var NullableLendingRate
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\NullableLendingRate")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Gedmo\Versioned
     *
     * @Groups({"tranche:read", "tranche:write"})
     */
    private $rate;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     */
    private $expectedReleasingDate;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     *
     * @Assert\Date
     *
     * @Gedmo\Versioned
     */
    private $expectedStartingDate;

    /**
     * @var TrancheFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TrancheFee", mappedBy="tranche", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private $trancheFees;

    /**
     * @var TrancheOffer[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TrancheOffer", mappedBy="tranche")
     * @ORM\OrderBy({"added": "DESC"})
     */
    private $trancheOffers;

    /**
     * @var TrancheAttribute[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TrancheAttribute", mappedBy="tranche", cascade={"persist"}, orphanRemoval=true)
     */
    private $trancheAttributes;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private $comment;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private $syndicated;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Expression(
     *     expression="(!this.isSyndicated() && this.getUnsyndicatedFunderType()) || this.isSyndicated() && this.getUnsyndicatedFunderType() === null",
     *     message="Tranche.unsyndicatedFunderType.expression"
     * )
     * @Assert\Choice({Tranche::UNSYNDICATED_FUNDER_TYPE_ARRANGER, Tranche::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY})
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private $unsyndicatedFunderType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\Expression(
     *     expression="(this.getUnsyndicatedFunderType() === constant('\\Unilend\\Entity\\Tranche::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY') && this.getThirdPartyFunder())
     *          || (this.getUnsyndicatedFunderType() !== constant('\\Unilend\\Entity\\Tranche::UNSYNDICATED_FUNDER_TYPE_THIRD_PARTY') && this.getThirdPartyFunder() === null)",
     *     message="Tranche.thirdPartyFunder.expression"
     * )
     *
     * @Groups({"tranche:write", "tranche:read"})
     */
    private $thirdPartyFunder;

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
    private $color;

    /**
     * @param Project $project
     * @param Money   $money
     *
     * @throws Exception
     */
    public function __construct(Project $project, Money $money)
    {
        $this->money             = $money;
        $this->rate              = new NullableLendingRate();
        $this->trancheFees       = new ArrayCollection();
        $this->trancheOffers     = new ArrayCollection();
        $this->trancheAttributes = new ArrayCollection();
        $this->added             = new DateTimeImmutable();
        $this->project           = $project;
        $this->syndicated        = true;
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
     * @param array|null                     $status
     * @param ProjectParticipationOffer|null $projectOffer
     *
     * @return TrancheOffer[]|ArrayCollection
     */
    public function getTrancheOffer(?array $status = null, ?ProjectParticipationOffer $projectOffer = null): iterable
    {
        $criteria = new Criteria();

        if (null !== $status) {
            $criteria->andWhere(Criteria::expr()->in('status', $status));
        }

        if (null !== $projectOffer) {
            $criteria->andWhere(Criteria::expr()->eq('projectOffer', $projectOffer));
        }

        return $this->trancheOffers->matching($criteria);
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @return Tranche
     */
    public function addTrancheOffer(TrancheOffer $trancheOffer): Tranche
    {
        $trancheOffer->setTranche($this);

        if (false === $this->trancheOffers->contains($trancheOffer)) {
            $this->trancheOffers->add($trancheOffer);
        }

        return $this;
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @return Tranche
     */
    public function removeTrancheOffer(TrancheOffer $trancheOffer): Tranche
    {
        if ($this->trancheOffers->contains($trancheOffer)) {
            $this->trancheOffers->removeElement($trancheOffer);
        }

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
        $this->thirdPartyFunder = $thirdPartyFunder;

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
}
