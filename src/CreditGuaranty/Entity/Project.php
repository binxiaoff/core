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
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramAwareInterface;
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramChoiceOptionCarrierInterface;
use Unilend\CreditGuaranty\Entity\Traits\AddressTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "creditGuaranty:project:read",
 *         "money:read",
 *         "nullableMoney:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "creditGuaranty:project:write",
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
 * @ORM\Table(name="credit_guaranty_project")
 * @ORM\HasLifecycleCallbacks
 */
class Project implements ProgramAwareInterface, ProgramChoiceOptionCarrierInterface
{
    use PublicizeIdentityTrait;
    use AddressTrait;
    use TimestampableTrait;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\Reservation", inversedBy="project")
     * @ORM\JoinColumn(name="id_reservation", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private Reservation $reservation;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_investment_thematic", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:project:write"})
     */
    private ?ProgramChoiceOption $investmentThematic = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_investment_type", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:project:write"})
     */
    private ?ProgramChoiceOption $investmentType = null;

    /**
     * @ORM\Column(length=1200, nullable=true)
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private ?string $detail;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_aid_intensity", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:project:write"})
     */
    private ?ProgramChoiceOption $aidIntensity = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_additional_guaranty", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:project:write"})
     */
    private ?ProgramChoiceOption $additionalGuaranty = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_agricultural_branch", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:project:write"})
     */
    private ?ProgramChoiceOption $agriculturalBranch = null;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private Money $fundingMoney;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $contribution;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $eligibleFeiCredit;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $totalFeiCredit;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $tangibleFeiCredit;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $intangibleFeiCredit;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $creditExcludingFei;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $grant;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $landValue;

    public function __construct(Reservation $reservation, Money $fundingMoney)
    {
        $this->reservation         = $reservation;
        $this->fundingMoney        = $fundingMoney;
        $this->contribution        = new NullableMoney();
        $this->eligibleFeiCredit   = new NullableMoney();
        $this->totalFeiCredit      = new NullableMoney();
        $this->tangibleFeiCredit   = new NullableMoney();
        $this->intangibleFeiCredit = new NullableMoney();
        $this->creditExcludingFei  = new NullableMoney();
        $this->grant               = new NullableMoney();
        $this->landValue           = new NullableMoney();
        $this->added               = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getProgram(): Program
    {
        return $this->getReservation()->getProgram();
    }

    public function getInvestmentThematic(): ?ProgramChoiceOption
    {
        return $this->investmentThematic;
    }

    public function setInvestmentThematic(?ProgramChoiceOption $investmentThematic): Project
    {
        $this->investmentThematic = $investmentThematic;

        return $this;
    }

    /**
     * @SerializedName("investmentThematic")
     *
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getInvestmentThematicDescription(): ?string
    {
        if ($this->investmentThematic) {
            return $this->investmentThematic->getDescription();
        }

        return null;
    }

    public function getInvestmentType(): ?ProgramChoiceOption
    {
        return $this->investmentType;
    }

    public function setInvestmentType(?ProgramChoiceOption $investmentType): Project
    {
        $this->investmentType = $investmentType;

        return $this;
    }

    /**
     * @SerializedName("investmentType")
     *
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getInvestmentTypeDescription(): ?string
    {
        if ($this->investmentType) {
            return $this->investmentType->getDescription();
        }

        return null;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(?string $detail): Project
    {
        $this->detail = $detail;

        return $this;
    }

    public function getAidIntensity(): ?ProgramChoiceOption
    {
        return $this->aidIntensity;
    }

    public function setAidIntensity(?ProgramChoiceOption $aidIntensity): Project
    {
        $this->aidIntensity = $aidIntensity;

        return $this;
    }

    /**
     * @SerializedName("aidIntensity")
     *
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAidIntensityDescription(): ?string
    {
        if ($this->aidIntensity) {
            return $this->aidIntensity->getDescription();
        }

        return null;
    }

    public function getAdditionalGuaranty(): ?ProgramChoiceOption
    {
        return $this->additionalGuaranty;
    }

    public function setAdditionalGuaranty(?ProgramChoiceOption $additionalGuaranty): Project
    {
        $this->additionalGuaranty = $additionalGuaranty;

        return $this;
    }

    /**
     * @SerializedName("additionalGuaranty")
     *
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAdditionalGuarantyDescription(): ?string
    {
        if ($this->additionalGuaranty) {
            return $this->additionalGuaranty->getDescription();
        }

        return null;
    }

    public function getAgriculturalBranch(): ?ProgramChoiceOption
    {
        return $this->agriculturalBranch;
    }

    public function setAgriculturalBranch(?ProgramChoiceOption $agriculturalBranch): Project
    {
        $this->agriculturalBranch = $agriculturalBranch;

        return $this;
    }

    /**
     * @SerializedName("agriculturalBranch")
     *
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAgriculturalBranchDescription(): ?string
    {
        if ($this->agriculturalBranch) {
            return $this->agriculturalBranch->getDescription();
        }

        return null;
    }

    public function getFundingMoney(): Money
    {
        return $this->fundingMoney;
    }

    public function setFundingMoney(Money $fundingMoney): Project
    {
        $this->fundingMoney = $fundingMoney;

        return $this;
    }

    public function getContribution(): NullableMoney
    {
        return $this->contribution;
    }

    public function setContribution(NullableMoney $contribution): Project
    {
        $this->contribution = $contribution;

        return $this;
    }

    public function getEligibleFeiCredit(): NullableMoney
    {
        return $this->eligibleFeiCredit;
    }

    public function setEligibleFeiCredit(NullableMoney $eligibleFeiCredit): Project
    {
        $this->eligibleFeiCredit = $eligibleFeiCredit;

        return $this;
    }

    public function getTotalFeiCredit(): NullableMoney
    {
        return $this->totalFeiCredit;
    }

    public function setTotalFeiCredit(NullableMoney $totalFeiCredit): Project
    {
        $this->totalFeiCredit = $totalFeiCredit;

        return $this;
    }

    public function getTangibleFeiCredit(): NullableMoney
    {
        return $this->tangibleFeiCredit;
    }

    public function setTangibleFeiCredit(NullableMoney $tangibleFeiCredit): Project
    {
        $this->tangibleFeiCredit = $tangibleFeiCredit;

        return $this;
    }

    public function getIntangibleFeiCredit(): NullableMoney
    {
        return $this->intangibleFeiCredit;
    }

    public function setIntangibleFeiCredit(NullableMoney $intangibleFeiCredit): Project
    {
        $this->intangibleFeiCredit = $intangibleFeiCredit;

        return $this;
    }

    public function getCreditExcludingFei(): NullableMoney
    {
        return $this->creditExcludingFei;
    }

    public function setCreditExcludingFei(NullableMoney $creditExcludingFei): Project
    {
        $this->creditExcludingFei = $creditExcludingFei;

        return $this;
    }

    public function getGrant(): NullableMoney
    {
        return $this->grant;
    }

    public function setGrant(NullableMoney $grant): Project
    {
        $this->grant = $grant;

        return $this;
    }

    public function isReceivingGrant(): bool
    {
        return null !== $this->grant->getAmount() || '0' !== $this->grant->getAmount();
    }

    public function getLandValue(): NullableMoney
    {
        return $this->landValue;
    }

    public function setLandValue(NullableMoney $landValue): Project
    {
        $this->landValue = $landValue;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function checkBalance(): bool
    {
        $program    = $this->reservation->getProgram();
        $totalFunds = $this->getTotalFunds($program);

        return MoneyCalculator::compare($totalFunds, $program->getFunds()) <= 0;
    }

    public function checkQuota(): bool
    {
        $program       = $this->reservation->getProgram();
        $participation = $program->getParticipations()->get($this->reservation->getManagingCompany()->getId());

        if (false === $participation instanceof Participation) {
            throw new \RuntimeException(\sprintf(
                'Cannot find the participation company %d for reservation %d. Please check the data.',
                $this->reservation->getManagingCompany()->getId(),
                $this->getId()
            ));
        }

        $ratio = MoneyCalculator::ratio($this->getFundingMoney(), $program->getFunds());

        return \bccomp((string) $ratio, $participation->getQuota(), 4) <= 0;
    }

    public function checkGradeAllocation(): bool
    {
        $program    = $this->reservation->getProgram();
        $grade      = $this->reservation->getBorrower()->getGrade();
        $gradeFunds = $this->getTotalFunds($program, ['grade' => $grade]);
        $ratio      = MoneyCalculator::ratio($gradeFunds, $program->getFunds());
        /** @var ProgramGradeAllocation $programGradeAllocation */
        $programGradeAllocation = $program->getProgramGradeAllocations()->get($grade);

        return \bccomp((string) $ratio, $programGradeAllocation->getMaxAllocationRate(), 4) <= 0;
    }

    public function checkBorrowerTypeAllocation(): bool
    {
        $program      = $this->reservation->getProgram();
        $borrowerType = $this->reservation->getBorrower()->getBorrowerType();
        if (false === $borrowerType instanceof ProgramChoiceOption) {
            throw new \RuntimeException(\sprintf(
                'Cannot find the borrower type %d for reservation %d. Please check the data.',
                $borrowerType->getId(),
                $this->reservation->getId()
            ));
        }
        $borrowerTypeFunds = $this->getTotalFunds($program, ['borrowerType' => $borrowerType->getId()]);
        $ratio             = MoneyCalculator::ratio($borrowerTypeFunds, $program->getFunds());
        /** @var ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation */
        $programBorrowerTypeAllocation = $program->getProgramBorrowerTypeAllocations()->get($borrowerType->getId());

        return \bccomp((string) $ratio, $programBorrowerTypeAllocation->getMaxAllocationRate(), 4) <= 0;
    }

    private function getTotalFunds(Program $program, array $filters = []): MoneyInterface
    {
        // Since the current project can be in the "total" or not according to its status,
        // we exclude it from the "total", then add it back manually to the "total", so that we get always the same "total" all the time.
        $filters = \array_merge(['exclude' => $this->getId()], $filters);

        return MoneyCalculator::add($program->getTotalProjectFunds($filters), $this->getFundingMoney());
    }
}
