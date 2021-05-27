<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Address;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "creditGuaranty:borrowerBusinessActivity:read",
 *         "nullableMoney:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "creditGuaranty:borrowerBusinessActivity:write",
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
 * @ORM\Table(name="credit_guaranty_borrower_business_activity")
 * @ORM\HasLifecycleCallbacks
 */
class BorrowerBusinessActivity
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\CreditGuaranty\Entity\Reservation", mappedBy="borrowerBusinessActivity")
     */
    private Reservation $reservation;

    /**
     * @ORM\Column(type="string", length=14, nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read", "creditGuaranty:borrowerBusinessActivity:write"})
     */
    private ?string $siret;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Address")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read", "creditGuaranty:borrowerBusinessActivity:write"})
     */
    private Address $address;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Assert\Positive
     *
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read", "creditGuaranty:borrowerBusinessActivity:write"})
     */
    private ?int $employeesNumber;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read", "creditGuaranty:borrowerBusinessActivity:write"})
     */
    private NullableMoney $lastYearTurnover;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read", "creditGuaranty:borrowerBusinessActivity:write"})
     */
    private NullableMoney $fiveYearsAverageTurnover;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read", "creditGuaranty:borrowerBusinessActivity:write"})
     */
    private NullableMoney $totalAssets;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read", "creditGuaranty:borrowerBusinessActivity:write"})
     */
    private NullableMoney $grant;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $subsidiary;

    public function __construct()
    {
        $this->address                  = new Address();
        $this->lastYearTurnover         = new NullableMoney();
        $this->fiveYearsAverageTurnover = new NullableMoney();
        $this->totalAssets              = new NullableMoney();
        $this->grant                    = new NullableMoney();
        $this->added                    = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): BorrowerBusinessActivity
    {
        $this->siret = $siret;

        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): BorrowerBusinessActivity
    {
        $this->address = $address;

        return $this;
    }

    public function getEmployeesNumber(): ?int
    {
        return $this->employeesNumber;
    }

    public function setEmployeesNumber(?int $employeesNumber): BorrowerBusinessActivity
    {
        $this->employeesNumber = $employeesNumber;

        return $this;
    }

    public function getLastYearTurnover(): NullableMoney
    {
        return $this->lastYearTurnover;
    }

    public function setLastYearTurnover(NullableMoney $lastYearTurnover): BorrowerBusinessActivity
    {
        $this->lastYearTurnover = $lastYearTurnover;

        return $this;
    }

    public function getFiveYearsAverageTurnover(): NullableMoney
    {
        return $this->fiveYearsAverageTurnover;
    }

    public function setFiveYearsAverageTurnover(NullableMoney $fiveYearsAverageTurnover): BorrowerBusinessActivity
    {
        $this->fiveYearsAverageTurnover = $fiveYearsAverageTurnover;

        return $this;
    }

    public function getTotalAssets(): NullableMoney
    {
        return $this->totalAssets;
    }

    public function setTotalAssets(NullableMoney $totalAssets): BorrowerBusinessActivity
    {
        $this->totalAssets = $totalAssets;

        return $this;
    }

    public function getGrant(): NullableMoney
    {
        return $this->grant;
    }

    public function setGrant(NullableMoney $grant): BorrowerBusinessActivity
    {
        $this->grant = $grant;

        return $this;
    }

    public function getSubsidiary(): ?bool
    {
        return $this->subsidiary;
    }

    public function setSubsidiary(?bool $subsidiary): BorrowerBusinessActivity
    {
        $this->subsidiary = $subsidiary;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read"})
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @Groups({"creditGuaranty:borrowerBusinessActivity:read"})
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }
}
