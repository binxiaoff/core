<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Constant\CAInternalRating;
use KLS\Core\Entity\Constant\CAInternalRetailRating;
use KLS\Core\Entity\Constant\CARatingType;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\ProgramAwareInterface;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\ProgramChoiceOptionCarrierInterface;
use KLS\CreditGuaranty\FEI\Entity\Traits\AddressTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:borrower:read",
 *             "nullableMoney:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:borrower:update",
 *             "creditGuaranty:programChoiceOption:write",
 *             "nullableMoney:write",
 *         },
 *         "openapi_definition_name": "update",
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
 * @ORM\Table(name="credit_guaranty_borrower")
 * @ORM\HasLifecycleCallbacks
 */
class Borrower implements ProgramAwareInterface, ProgramChoiceOptionCarrierInterface
{
    use PublicizeIdentityTrait;
    use AddressTrait;
    use TimestampableTrait;

    /**
     * @ORM\OneToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Reservation", mappedBy="borrower")
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    private Reservation $reservation;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?string $beneficiaryName = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_borrower_type")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    private ?ProgramChoiceOption $borrowerType = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $youngFarmer = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $creationInProgress = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $subsidiary = null;

    /**
     * L’emprunteur est-il considéré comme étant économiquement viable ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $economicallyViable = null;

    /**
     * L’emprunteur bénéficie-t-il du transfert de bénéfice,
     * à savoir d’une réduction du taux et une limitation des garanties ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $benefitingProfitTransfer = null;

    /**
     * L’emprunteur est-il côté sur un marché boursier ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $listedOnStockMarket = null;

    /**
     * L’emprunteur est-il établi dans une juridiction Non coopérative ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $inNonCooperativeJurisdiction = null;

    /**
     * L’emprunteur fait-il l’objet d’une injonction de récupération non exécutée ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $subjectOfUnperformedRecoveryOrder = null;

    /**
     * L’emprunteur fait-il l’objet d’un plan de restructuration (aide au sauvetage, aide à la restructuration) ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $subjectOfRestructuringPlan = null;

    /**
     * Le projet a-t-il bénéficié d’un financement du FEAGA/OCM pour le même objet ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $projectReceivedFeagaOcmFunding = null;

    /**
     * Les dates des justificatifs de l’objet du prêt sont postérieurs à celle de la demande du prêt ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $loanSupportingDocumentsDatesAfterApplication = null;

    /**
     * Le prêt permet-il de refinancer ou restructurer un prêt existant ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $loanAllowedRefinanceRestructure = null;

    /**
     * La transaction est-elle affectée par une irrégularité ou une fraude ?
     *
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?bool $transactionAffected = null;

    /**
     * @ORM\Column(length=100, nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update"})
     */
    private ?string $companyName = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update"})
     */
    private ?DateTimeImmutable $activityStartDate = null;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Length(max=200)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update"})
     */
    private ?string $registrationNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_legal_form")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    private ?ProgramChoiceOption $legalForm = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_company_naf_code")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    private ?ProgramChoiceOption $companyNafCode = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Positive
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?int $employeesNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_exploitation_size")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    private ?ProgramChoiceOption $exploitationSize = null;

    /**
     * @ORM\Embedded(class=NullableMoney::class)
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private NullableMoney $turnover;

    /**
     * @ORM\Embedded(class=NullableMoney::class)
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private NullableMoney $totalAssets;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_target_type")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?ProgramChoiceOption $targetType = null;

    /**
     * @ORM\Column(length=10, nullable=true)
     *
     * @Assert\Expression(
     *     "this.isGradeValid()",
     *     message="CreditGuaranty.Borrower.grade.invalid"
     * )
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:update:draft"})
     */
    private ?string $grade = null;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
        $this->turnover    = new NullableMoney();
        $this->totalAssets = new NullableMoney();
        $this->added       = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getProgram(): Program
    {
        return $this->getReservation()->getProgram();
    }

    public function getBeneficiaryName(): ?string
    {
        return $this->beneficiaryName;
    }

    public function setBeneficiaryName(?string $beneficiaryName): Borrower
    {
        $this->beneficiaryName = $beneficiaryName;

        return $this;
    }

    public function getBorrowerType(): ?ProgramChoiceOption
    {
        return $this->borrowerType;
    }

    /**
     * @SerializedName("borrowerType")
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getBorrowerTypeDescription(): ?string
    {
        if ($this->borrowerType instanceof ProgramChoiceOption) {
            return $this->borrowerType->getDescription();
        }

        return null;
    }

    public function setBorrowerType(?ProgramChoiceOption $borrowerType): Borrower
    {
        $this->borrowerType = $borrowerType;

        return $this;
    }

    public function isYoungFarmer(): ?bool
    {
        return $this->youngFarmer;
    }

    public function setYoungFarmer(?bool $youngFarmer): Borrower
    {
        $this->youngFarmer = $youngFarmer;

        return $this;
    }

    public function isCreationInProgress(): ?bool
    {
        return $this->creationInProgress;
    }

    public function setCreationInProgress(?bool $creationInProgress): Borrower
    {
        $this->creationInProgress = $creationInProgress;

        return $this;
    }

    public function isSubsidiary(): ?bool
    {
        return $this->subsidiary;
    }

    public function setSubsidiary(?bool $subsidiary): Borrower
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    public function isEconomicallyViable(): ?bool
    {
        return $this->economicallyViable;
    }

    public function setEconomicallyViable(?bool $economicallyViable): Borrower
    {
        $this->economicallyViable = $economicallyViable;

        return $this;
    }

    public function isBenefitingProfitTransfer(): ?bool
    {
        return $this->benefitingProfitTransfer;
    }

    public function setBenefitingProfitTransfer(?bool $benefitingProfitTransfer): Borrower
    {
        $this->benefitingProfitTransfer = $benefitingProfitTransfer;

        return $this;
    }

    public function isListedOnStockMarket(): ?bool
    {
        return $this->listedOnStockMarket;
    }

    public function setListedOnStockMarket(?bool $listedOnStockMarket): Borrower
    {
        $this->listedOnStockMarket = $listedOnStockMarket;

        return $this;
    }

    public function isInNonCooperativeJurisdiction(): ?bool
    {
        return $this->inNonCooperativeJurisdiction;
    }

    public function setInNonCooperativeJurisdiction(?bool $inNonCooperativeJurisdiction): Borrower
    {
        $this->inNonCooperativeJurisdiction = $inNonCooperativeJurisdiction;

        return $this;
    }

    public function isSubjectOfUnperformedRecoveryOrder(): ?bool
    {
        return $this->subjectOfUnperformedRecoveryOrder;
    }

    public function setSubjectOfUnperformedRecoveryOrder(?bool $subjectOfUnperformedRecoveryOrder): Borrower
    {
        $this->subjectOfUnperformedRecoveryOrder = $subjectOfUnperformedRecoveryOrder;

        return $this;
    }

    public function isSubjectOfRestructuringPlan(): ?bool
    {
        return $this->subjectOfRestructuringPlan;
    }

    public function setSubjectOfRestructuringPlan(?bool $subjectOfRestructuringPlan): Borrower
    {
        $this->subjectOfRestructuringPlan = $subjectOfRestructuringPlan;

        return $this;
    }

    public function isProjectReceivedFeagaOcmFunding(): ?bool
    {
        return $this->projectReceivedFeagaOcmFunding;
    }

    public function setProjectReceivedFeagaOcmFunding(?bool $projectReceivedFeagaOcmFunding): Borrower
    {
        $this->projectReceivedFeagaOcmFunding = $projectReceivedFeagaOcmFunding;

        return $this;
    }

    public function isLoanSupportingDocumentsDatesAfterApplication(): ?bool
    {
        return $this->loanSupportingDocumentsDatesAfterApplication;
    }

    public function setLoanSupportingDocumentsDatesAfterApplication(?bool $value): Borrower
    {
        $this->loanSupportingDocumentsDatesAfterApplication = $value;

        return $this;
    }

    public function isLoanAllowedRefinanceRestructure(): ?bool
    {
        return $this->loanAllowedRefinanceRestructure;
    }

    public function setLoanAllowedRefinanceRestructure(?bool $loanAllowedRefinanceRestructure): Borrower
    {
        $this->loanAllowedRefinanceRestructure = $loanAllowedRefinanceRestructure;

        return $this;
    }

    public function isTransactionAffected(): ?bool
    {
        return $this->transactionAffected;
    }

    public function setTransactionAffected(?bool $transactionAffected): Borrower
    {
        $this->transactionAffected = $transactionAffected;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): Borrower
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    /**
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    public function setAddressStreet(?string $street): Borrower
    {
        $this->addressStreet = $street;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressPostCode(): ?string
    {
        return $this->addressPostCode;
    }

    /**
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    public function setAddressPostCode(?string $postCode): Borrower
    {
        $this->addressPostCode = $postCode;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }

    /**
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    public function setAddressCity(?string $city): Borrower
    {
        $this->addressCity = $city;

        return $this;
    }

    /**
     * @SerializedName("addressDepartment")
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressDepartmentDescription(): ?string
    {
        if ($this->addressDepartment instanceof ProgramChoiceOption) {
            return $this->addressDepartment->getDescription();
        }

        return null;
    }

    /**
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    public function setAddressDepartment(?ProgramChoiceOption $department): Borrower
    {
        $this->addressDepartment = $department;

        return $this;
    }

    /**
     * @SerializedName("addressCountry")
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressCountryDescription(): ?string
    {
        if ($this->addressCountry instanceof ProgramChoiceOption) {
            return $this->addressCountry->getDescription();
        }

        return null;
    }

    /**
     * @Groups({"creditGuaranty:borrower:update:draft"})
     */
    public function setAddressCountry(?ProgramChoiceOption $country): Borrower
    {
        $this->addressCountry = $country;

        return $this;
    }

    public function getActivityStartDate(): ?DateTimeImmutable
    {
        return $this->activityStartDate;
    }

    public function setActivityStartDate(?DateTimeImmutable $activityStartDate): Borrower
    {
        $this->activityStartDate = $activityStartDate;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?string $registrationNumber): Borrower
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    public function getLegalForm(): ?ProgramChoiceOption
    {
        return $this->legalForm;
    }

    public function setLegalForm(?ProgramChoiceOption $legalForm): Borrower
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    /**
     * @SerializedName("legalForm")
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getLegalFormDescription(): ?string
    {
        if ($this->legalForm) {
            return $this->legalForm->getDescription();
        }

        return null;
    }

    public function getCompanyNafCode(): ?ProgramChoiceOption
    {
        return $this->companyNafCode;
    }

    public function setCompanyNafCode(ProgramChoiceOption $companyNafCode): Borrower
    {
        $this->companyNafCode = $companyNafCode;

        return $this;
    }

    /**
     * @SerializedName("companyNafCode")
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getCompanyNafCodeDescription(): ?string
    {
        if ($this->companyNafCode) {
            return $this->companyNafCode->getDescription();
        }

        return null;
    }

    public function getEmployeesNumber(): ?int
    {
        return $this->employeesNumber;
    }

    public function setEmployeesNumber(?int $employeesNumber): Borrower
    {
        $this->employeesNumber = $employeesNumber;

        return $this;
    }

    public function getExploitationSize(): ?ProgramChoiceOption
    {
        return $this->exploitationSize;
    }

    public function setExploitationSize(?ProgramChoiceOption $exploitationSize): Borrower
    {
        $this->exploitationSize = $exploitationSize;

        return $this;
    }

    /**
     * @SerializedName("exploitationSize")
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getExploitationSizeDescription(): ?string
    {
        if ($this->exploitationSize) {
            return $this->exploitationSize->getDescription();
        }

        return null;
    }

    public function getTurnover(): NullableMoney
    {
        return $this->turnover;
    }

    public function setTurnover(NullableMoney $turnover): Borrower
    {
        $this->turnover = $turnover;

        return $this;
    }

    public function getTotalAssets(): NullableMoney
    {
        return $this->totalAssets;
    }

    public function setTotalAssets(NullableMoney $totalAssets): Borrower
    {
        $this->totalAssets = $totalAssets;

        return $this;
    }

    public function getTargetType(): ?ProgramChoiceOption
    {
        return $this->targetType;
    }

    public function setTargetType(?ProgramChoiceOption $targetType): Borrower
    {
        $this->targetType = $targetType;

        return $this;
    }

    /**
     * @SerializedName("targetType")
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getTargetTypeDescription(): ?string
    {
        if ($this->targetType) {
            return $this->targetType->getDescription();
        }

        return null;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): Borrower
    {
        $this->grade = $grade;

        return $this;
    }

    public function isGradeValid(): bool
    {
        if (null === $this->grade) {
            return false;
        }

        switch ($this->reservation->getProgram()->getRatingType()) {
            case CARatingType::CA_INTERNAL_RETAIL_RATING:
                return \in_array($this->grade, CAInternalRetailRating::getConstList(), true);

            case CARatingType::CA_INTERNAL_RATING:
                return \in_array($this->grade, CAInternalRating::getConstList(), true);

            default:
                return false;
        }
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }
}
