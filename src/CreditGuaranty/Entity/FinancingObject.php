<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Interfaces\MoneyInterface;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Service\MoneyCalculator;
use Unilend\CreditGuaranty\Entity\Constant\GrossSubsidyEquivalent;
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramAwareInterface;
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramChoiceOptionCarrierInterface;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "creditGuaranty:financingObject:read",
 *         "money:read",
 *         "nullableMoney:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "creditGuaranty:financingObject:write",
 *         "money:write",
 *         "nullableMoney:write"
 *     }},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)"
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_financing_object")
 * @ORM\HasLifecycleCallbacks
 */
class FinancingObject implements ProgramAwareInterface, ProgramChoiceOptionCarrierInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Reservation", inversedBy="financingObjects")
     * @ORM\JoinColumn(name="id_reservation", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private Reservation $reservation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private ?bool $supportingGenerationsRenewal = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private ?string $name;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_financing_object_type", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:financingObject:write"})
     */
    private ?ProgramChoiceOption $financingObjectType = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_loan_naf_code", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:financingObject:write"})
     */
    private ?ProgramChoiceOption $loanNafCode = null;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private Money $loanMoney;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private NullableMoney $bfrValue;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_loan_type", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private ?ProgramChoiceOption $loanType = null;

    /**
     * Duration in month.
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Assert\GreaterThanOrEqual(1)
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("null === this.getProgram().isEsbCalculationActivated()"),
     *     @Assert\Expression("false === this.getProgram().isEsbCalculationActivated()"),
     *     @Assert\Expression("true === this.getProgram().isEsbCalculationActivated() && null !== value")
     * }, message="CreditGuaranty.Reservation.financingObject.loanDuration.requiredForEsb", includeInternalMessages=false)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private ?int $loanDuration = null;

    /**
     * Duration in month.
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Assert\GreaterThanOrEqual(1)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private ?int $loanDeferral = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_loan_periodicity", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private ?ProgramChoiceOption $loanPeriodicity = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_investment_location", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private ?ProgramChoiceOption $investmentLocation = null;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("null === this.getProgram().isLoanReleasedOnInvoice()"),
     *     @Assert\Expression("false === this.getProgram().isLoanReleasedOnInvoice()"),
     *     @Assert\Expression("true === this.getProgram().isLoanReleasedOnInvoice() && false === value.isNull()")
     * }, message="CreditGuaranty.Reservation.financingObject.invoiceMoney.requiredForLoanReleasedOnInvoice", includeInternalMessages=false)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private NullableMoney $invoiceMoney;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("null === this.getProgram().isLoanReleasedOnInvoice()"),
     *     @Assert\Expression("false === this.getProgram().isLoanReleasedOnInvoice()"),
     *     @Assert\Expression("true === this.getProgram().isLoanReleasedOnInvoice() && false === value.isNull()")
     * }, message="CreditGuaranty.Reservation.financingObject.achievementMoney.requiredForLoanReleasedOnInvoice", includeInternalMessages=false)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private NullableMoney $achievementMoney;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private bool $mainLoan;

    public function __construct(
        Reservation $reservation,
        Money $loanMoney,
        bool $mainLoan
    ) {
        $this->reservation      = $reservation;
        $this->loanMoney        = $loanMoney;
        $this->bfrValue         = new NullableMoney();
        $this->invoiceMoney     = new NullableMoney();
        $this->achievementMoney = new NullableMoney();
        $this->mainLoan         = $mainLoan;
        $this->added            = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getProgram(): Program
    {
        return $this->getReservation()->getProgram();
    }

    public function isSupportingGenerationsRenewal(): ?bool
    {
        return $this->supportingGenerationsRenewal;
    }

    public function setSupportingGenerationsRenewal(?bool $supportingGenerationsRenewal): FinancingObject
    {
        $this->supportingGenerationsRenewal = $supportingGenerationsRenewal;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): FinancingObject
    {
        $this->name = $name;

        return $this;
    }

    public function getFinancingObjectType(): ?ProgramChoiceOption
    {
        return $this->financingObjectType;
    }

    public function setFinancingObjectType(?ProgramChoiceOption $financingObjectType): FinancingObject
    {
        $this->financingObjectType = $financingObjectType;

        return $this;
    }

    /**
     * @SerializedName("financingObjectType")
     *
     * @Groups({"creditGuaranty:financingObject:read"})
     */
    public function getFinancingObjectTypeDescription(): ?string
    {
        if ($this->financingObjectType instanceof ProgramChoiceOption) {
            return $this->financingObjectType->getDescription();
        }

        return null;
    }

    public function getLoanNafCode(): ?ProgramChoiceOption
    {
        return $this->loanNafCode;
    }

    public function setLoanNafCode(?ProgramChoiceOption $loanNafCode): FinancingObject
    {
        $this->loanNafCode = $loanNafCode;

        return $this;
    }

    /**
     * @SerializedName("loanNafCode")
     *
     * @Groups({"creditGuaranty:financingObject:read"})
     */
    public function getLoanNafCodeDescription(): ?string
    {
        if ($this->loanNafCode instanceof ProgramChoiceOption) {
            return $this->loanNafCode->getDescription();
        }

        return null;
    }

    public function getLoanMoney(): Money
    {
        return $this->loanMoney;
    }

    public function setLoanMoney(Money $loanMoney): FinancingObject
    {
        $this->loanMoney = $loanMoney;

        return $this;
    }

    public function getBfrValue(): NullableMoney
    {
        return $this->bfrValue;
    }

    public function setBfrValue(NullableMoney $bfrValue): FinancingObject
    {
        $this->bfrValue = $bfrValue;

        return $this;
    }

    public function getLoanType(): ?ProgramChoiceOption
    {
        return $this->loanType;
    }

    public function setLoanType(?ProgramChoiceOption $loanType): FinancingObject
    {
        $this->loanType = $loanType;

        return $this;
    }

    /**
     * @SerializedName("loanType")
     *
     * @Groups({"creditGuaranty:financingObject:read"})
     */
    public function getLoanTypeDescription(): ?string
    {
        if ($this->loanType instanceof ProgramChoiceOption) {
            return $this->loanType->getDescription();
        }

        return null;
    }

    public function getLoanDuration(): ?int
    {
        return $this->loanDuration;
    }

    public function setLoanDuration(?int $loanDuration): FinancingObject
    {
        $this->loanDuration = $loanDuration;

        return $this;
    }

    public function getLoanDeferral(): ?int
    {
        return $this->loanDeferral;
    }

    public function setLoanDeferral(?int $loanDeferral): FinancingObject
    {
        $this->loanDeferral = $loanDeferral;

        return $this;
    }

    public function getLoanPeriodicity(): ?ProgramChoiceOption
    {
        return $this->loanPeriodicity;
    }

    public function setLoanPeriodicity(?ProgramChoiceOption $loanPeriodicity): FinancingObject
    {
        $this->loanPeriodicity = $loanPeriodicity;

        return $this;
    }

    /**
     * @SerializedName("loanPeriodicity")
     *
     * @Groups({"creditGuaranty:financingObject:read"})
     */
    public function getLoanPeriodicityDescription(): ?string
    {
        if ($this->loanPeriodicity instanceof ProgramChoiceOption) {
            return $this->loanPeriodicity->getDescription();
        }

        return null;
    }

    public function getInvestmentLocation(): ?ProgramChoiceOption
    {
        return $this->investmentLocation;
    }

    public function setInvestmentLocation(?ProgramChoiceOption $investmentLocation): FinancingObject
    {
        $this->investmentLocation = $investmentLocation;

        return $this;
    }

    /**
     * @SerializedName("investmentLocation")
     *
     * @Groups({"creditGuaranty:financingObject:read"})
     */
    public function getInvestmentLocationDescription(): ?string
    {
        if ($this->investmentLocation instanceof ProgramChoiceOption) {
            return $this->investmentLocation->getDescription();
        }

        return null;
    }

    public function getInvoiceMoney(): NullableMoney
    {
        return $this->invoiceMoney;
    }

    public function setInvoiceMoney(NullableMoney $invoiceMoney): FinancingObject
    {
        $this->invoiceMoney = $invoiceMoney;

        return $this;
    }

    public function getAchievementMoney(): NullableMoney
    {
        return $this->achievementMoney;
    }

    public function setAchievementMoney(NullableMoney $achievementMoney): FinancingObject
    {
        $this->achievementMoney = $achievementMoney;

        return $this;
    }

    public function isMainLoan(): bool
    {
        return $this->mainLoan;
    }

    public function setMainLoan(bool $mainLoan): FinancingObject
    {
        $this->mainLoan = $mainLoan;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:financingObject:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @Groups({"creditGuaranty:financingObject:read"})
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    /**
     * Montant équivalent de subvention Brut (ESB).
     *
     * @Groups({"creditGuaranty:financingObject:read"})
     */
    public function getGrossSubsidyEquivalent(): MoneyInterface
    {
        $esb = MoneyCalculator::multiply($this->getLoanMoney(), (float) $this->getProgram()->getGuarantyCoverage());

        return MoneyCalculator::multiply($esb, (float) \bcmul((string) $this->getLoanDuration(), (string) GrossSubsidyEquivalent::FACTOR, 4));
    }
}
