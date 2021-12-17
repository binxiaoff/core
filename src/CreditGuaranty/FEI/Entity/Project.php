<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Interfaces\MoneyInterface;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Service\MoneyCalculator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Constant\GrossSubsidyEquivalent;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\ProgramAwareInterface;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\ProgramChoiceOptionCarrierInterface;
use KLS\CreditGuaranty\FEI\Entity\Traits\AddressTrait;
use RuntimeException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:project:read",
 *             "money:read",
 *             "nullableMoney:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:project:write",
 *             "money:write",
 *             "nullableMoney:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)",
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)",
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object)"
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
     * @ORM\OneToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Reservation", mappedBy="project")
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private Reservation $reservation;

    /**
     * @var ProgramChoiceOption[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinTable(name="credit_guaranty_project_investment_thematic")
     *
     * @Assert\All({
     *     @Assert\Choice(callback="getAvailableInvestmentThematics")
     * })
     *
     * @ApiProperty(readableLink=false)
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private Collection $investmentThematics;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
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
    private ?string $detail = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_aid_intensity", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:project:write"})
     */
    private ?ProgramChoiceOption $aidIntensity = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_additional_guaranty", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:project:write"})
     */
    private ?ProgramChoiceOption $additionalGuaranty = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_agricultural_branch", nullable=true)
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:project:write"})
     */
    private ?ProgramChoiceOption $agriculturalBranch = null;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $fundingMoney;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $contribution;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $eligibleFeiCredit;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $totalFeiCredit;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $tangibleFeiCredit;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $intangibleFeiCredit;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $creditExcludingFei;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $grant;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\NullableMoney")
     *
     * @Groups({"creditGuaranty:project:read", "creditGuaranty:project:write"})
     */
    private NullableMoney $landValue;

    public function __construct(Reservation $reservation)
    {
        $this->reservation         = $reservation;
        $this->investmentThematics = new ArrayCollection();
        $this->fundingMoney        = new NullableMoney();
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

    /**
     * @return ProgramChoiceOption[]|Collection
     */
    public function getInvestmentThematics(): Collection
    {
        return $this->investmentThematics;
    }

    public function addInvestmentThematic(ProgramChoiceOption $programChoiceOption): Project
    {
        if (false === $this->investmentThematics->exists($programChoiceOption->getEquivalenceChecker())) {
            $this->investmentThematics->add($programChoiceOption);
        }

        return $this;
    }

    public function removeInvestmentThematic(ProgramChoiceOption $programChoiceOption): Project
    {
        if ($this->investmentThematics->exists($programChoiceOption->getEquivalenceChecker())) {
            $this->investmentThematics->removeElement($programChoiceOption);
        }

        return $this;
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

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    /**
     * @Groups({"creditGuaranty:project:write"})
     */
    public function setAddressStreet(?string $street): Project
    {
        $this->addressStreet = $street;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAddressPostCode(): ?string
    {
        return $this->addressPostCode;
    }

    /**
     * @Groups({"creditGuaranty:project:write"})
     */
    public function setAddressPostCode(?string $postCode): Project
    {
        $this->addressPostCode = $postCode;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }

    /**
     * @Groups({"creditGuaranty:project:write"})
     */
    public function setAddressCity(?string $city): Project
    {
        $this->addressCity = $city;

        return $this;
    }

    /**
     * @SerializedName("addressDepartment")
     *
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAddressDepartmentDescription(): ?string
    {
        if ($this->addressDepartment instanceof ProgramChoiceOption) {
            return $this->addressDepartment->getDescription();
        }

        return null;
    }

    /**
     * @Groups({"creditGuaranty:project:write"})
     */
    public function setAddressDepartment(?ProgramChoiceOption $department): Project
    {
        $this->addressDepartment = $department;

        return $this;
    }

    /**
     * @SerializedName("addressCountry")
     *
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getAddressCountryDescription(): ?string
    {
        if ($this->addressCountry instanceof ProgramChoiceOption) {
            return $this->addressCountry->getDescription();
        }

        return null;
    }

    /**
     * @Groups({"creditGuaranty:project:write"})
     */
    public function setAddressCountry(?ProgramChoiceOption $country): Project
    {
        $this->addressCountry = $country;

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

    public function getFundingMoney(): NullableMoney
    {
        return $this->fundingMoney;
    }

    public function setFundingMoney(NullableMoney $fundingMoney): Project
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

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getMaxFeiCredit(): MoneyInterface
    {
        $programMaxFeiCredit = $this->getProgram()->getMaxFeiCredit();

        if (false === ($this->getAidIntensity() instanceof ProgramChoiceOption)) {
            return new NullableMoney();
        }

        $publicAidLimit = MoneyCalculator::multiply(
            $this->getTotalFeiCredit()->isNull() ? 0 : $this->getTotalFeiCredit(),
            (float) $this->getAidIntensity()->getDescription()
        );
        $remainingGrantLimit = MoneyCalculator::subtract(
            $publicAidLimit,
            $this->getGrant()->isNull() ? 0 : $this->getGrant()
        );
        $maxFeiCredit = MoneyCalculator::multiply(
            $remainingGrantLimit,
            (float) $this->getProgram()->getGuarantyCoverage()
        );
        $duration = \bcmul(
            (string) $this->getProgram()->getGuarantyDuration(),
            (string) GrossSubsidyEquivalent::FACTOR,
            4
        );
        $maxFeiCredit = MoneyCalculator::multiply($maxFeiCredit, (float) $duration);

        return MoneyCalculator::max($programMaxFeiCredit, $maxFeiCredit);
    }

    /**
     * @Groups({"creditGuaranty:project:read"})
     */
    public function getTotalGrossSubsidyEquivalent(): MoneyInterface
    {
        $financingObjects = $this->getReservation()->getFinancingObjects();

        if ($financingObjects->count() < 1) {
            return new NullableMoney();
        }

        $grossSubsidyEquivalents = $financingObjects->map(
            static fn (FinancingObject $financingObject) => $financingObject->getGrossSubsidyEquivalent()
        )->toArray();

        return MoneyCalculator::sum($grossSubsidyEquivalents);
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
            throw new RuntimeException(\sprintf(
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

        /** @var ProgramGradeAllocation|null $programGradeAllocation */
        $programGradeAllocation = $program->getProgramGradeAllocations()->get($grade);

        if (false === ($programGradeAllocation instanceof ProgramGradeAllocation)) {
            return false;
        }

        return \bccomp((string) $ratio, $programGradeAllocation->getMaxAllocationRate(), 4) <= 0;
    }

    public function checkBorrowerTypeAllocation(): bool
    {
        $program      = $this->reservation->getProgram();
        $borrowerType = $this->reservation->getBorrower()->getBorrowerType();
        if (false === ($borrowerType instanceof ProgramChoiceOption)) {
            throw new RuntimeException(\sprintf(
                'Cannot find the borrower type for reservation %d. Please check the data.',
                $this->reservation->getId()
            ));
        }
        $borrowerTypeFunds = $this->getTotalFunds($program, ['borrowerType' => $borrowerType->getId()]);
        $ratio             = MoneyCalculator::ratio($borrowerTypeFunds, $program->getFunds());
        /** @var ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation */
        $programBorrowerTypeAllocation = $program->getProgramBorrowerTypeAllocations()->get($borrowerType->getId());

        return \bccomp((string) $ratio, $programBorrowerTypeAllocation->getMaxAllocationRate(), 4) <= 0;
    }

    /**
     * @return ProgramChoiceOption[]|array
     */
    public function getAvailableInvestmentThematics(): array
    {
        return $this->getProgram()->getProgramChoiceOptions()
            ->filter(
                fn (ProgramChoiceOption $pco) => FieldAlias::INVESTMENT_THEMATIC === $pco->getField()->getFieldAlias()
            )
            ->toArray()
        ;
    }

    private function getTotalFunds(Program $program, array $filters = []): MoneyInterface
    {
        // Since the current project can be in the "total" or not according to its status,
        // we exclude it from the "total", then add it back manually to the "total",
        // so that we get always the same "total" all the time.
        $filters = \array_merge(['exclude' => $this->getId()], $filters);

        return MoneyCalculator::add($program->getTotalProjectFunds($filters), $this->getFundingMoney());
    }
}
