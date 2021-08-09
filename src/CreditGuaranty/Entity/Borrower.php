<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Entity;

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
use KLS\CreditGuaranty\Entity\Interfaces\ProgramAwareInterface;
use KLS\CreditGuaranty\Entity\Interfaces\ProgramChoiceOptionCarrierInterface;
use KLS\CreditGuaranty\Entity\Traits\AddressTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "creditGuaranty:borrower:read",
 *         "nullableMoney:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "creditGuaranty:borrower:write",
 *         "creditGuaranty:programChoiceOption:write",
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
 * @ORM\Table(name="credit_guaranty_borrower")
 * @ORM\HasLifecycleCallbacks
 */
class Borrower implements ProgramAwareInterface, ProgramChoiceOptionCarrierInterface
{
    use PublicizeIdentityTrait;
    use AddressTrait;
    use TimestampableTrait;

    /**
     * @ORM\OneToOne(targetEntity="KLS\CreditGuaranty\Entity\Reservation", inversedBy="borrower")
     * @ORM\JoinColumn(name="id_reservation", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private Reservation $reservation;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?string $beneficiaryName = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_borrower_type")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:write"})
     */
    private ?ProgramChoiceOption $borrowerType = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?bool $youngFarmer = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?bool $creationInProgress = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $subsidiary = null;

    /**
     * @ORM\Column(length=100)
     *
     * @Assert\NotBlank
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private string $companyName;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @Groups({"timestampable:read"})
     */
    private ?DateTimeImmutable $activityStartDate = null;

    /**
     * @ORM\Column(type="string", length=14, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?string $siret = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?string $taxNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_legal_form")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:write"})
     */
    private ?ProgramChoiceOption $legalForm = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_company_naf_code")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:write"})
     */
    private ?ProgramChoiceOption $companyNafCode = null;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Assert\Positive
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?int $employeesNumber = null;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_exploitation_size")
     *
     * @Assert\Expression("value === null || value.getProgram() === this.getProgram()")
     *
     * @Groups({"creditGuaranty:borrower:write"})
     */
    private ?ProgramChoiceOption $exploitationSize = null;

    /**
     * @ORM\Embedded(class=NullableMoney::class)
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private NullableMoney $turnover;

    /**
     * @ORM\Embedded(class=NullableMoney::class)
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private NullableMoney $totalAssets;

    /**
     * @ORM\Column(length=10)
     *
     * @Assert\Expression(
     *     "this.isGradeValid()",
     *     message="CreditGuaranty.Borrower.grade.invalid"
     * )
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private string $grade;

    public function __construct(Reservation $reservation, string $companyName, string $grade)
    {
        $this->reservation = $reservation;
        $this->companyName = $companyName;
        $this->turnover    = new NullableMoney();
        $this->totalAssets = new NullableMoney();
        $this->grade       = $grade;
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

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressPostCode(): ?string
    {
        return $this->addressPostCode;
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }

    /**
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getAddressDepartment(): ?string
    {
        return $this->addressDepartment;
    }

    /**
     * @SerializedName("addressCountry")
     *
     * @Groups({"creditGuaranty:borrower:read"})
     */
    public function getActivityCountry(): ?string
    {
        if ($this->addressCountry instanceof ProgramChoiceOption) {
            return $this->addressCountry->getDescription();
        }

        return null;
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

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): Borrower
    {
        $this->siret = $siret;

        return $this;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(?string $taxNumber): Borrower
    {
        $this->taxNumber = $taxNumber;

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

    public function getGrade(): string
    {
        return $this->grade;
    }

    public function isGradeValid(): bool
    {
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
