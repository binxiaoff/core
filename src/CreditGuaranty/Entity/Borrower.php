<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\CAInternalRetailRating;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\Core\Entity\Embeddable\Address;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "creditGuaranty:borrower:read",
 *         "timestampable:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "creditGuaranty:borrower:write"
 *     }},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object.getReservation())"
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object.getReservation())"
 *         },
 *         "delete": {
 *             "security": "is_granted('delete', object.getReservation())"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object.getReservation())"
 *         },
 *         "get"
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_borrower")
 * @ORM\HasLifecycleCallbacks
 */
class Borrower
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\Reservation", mappedBy="borrower")
     */
    private Reservation $reservation;

    /**
     * @ORM\Column(length=100)
     *
     * @Assert\NotBlank
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private string $companyName;

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

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_borrower_type")
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?ProgramChoiceOption $borrowerType = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_legal_form")
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?ProgramChoiceOption $legalForm = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?string $taxNumber = null;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?string $beneficiaryName = null;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Address")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private Address $address;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"creditGuaranty:borrower:read", "creditGuaranty:borrower:write"})
     */
    private ?bool $creationInProgress = null;

    public function __construct(string $companyName, string $grade)
    {
        $this->companyName = $companyName;
        $this->grade       = $grade;
        $this->address     = new Address();
        $this->added       = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getGrade(): string
    {
        return $this->grade;
    }

    public function getBorrowerType(): ?ProgramChoiceOption
    {
        return $this->borrowerType;
    }

    public function setBorrowerType(?ProgramChoiceOption $borrowerType): Borrower
    {
        $this->borrowerType = $borrowerType;

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

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(?string $taxNumber): Borrower
    {
        $this->taxNumber = $taxNumber;

        return $this;
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

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): Borrower
    {
        $this->address = $address;

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
}
