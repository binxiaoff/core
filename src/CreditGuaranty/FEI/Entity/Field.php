<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     itemOperations={"get"},
 *     collectionOperations={"get"},
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"tag": "exact"})
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_field")
 *
 * @UniqueEntity({"fieldAlias"})
 */
class Field
{
    use PublicizeIdentityTrait;

    // These tags are used for reporting
    public const TAG_ELIGIBILITY = 'eligibility';
    public const TAG_INFO        = 'info';
    public const TAG_IMPORTED    = 'imported';
    public const TAG_CALCUL      = 'calcul';

    public const TYPE_LIST  = 'list'; // for fields of type pre-defined list or user-defined list
    public const TYPE_BOOL  = 'bool'; // for fields of type boolean "list" (yes or no)
    public const TYPE_OTHER = 'other'; // for other fields that we haven't yet managed

    public const VALUE_BOOL_YES = '1';
    public const VALUE_BOOL_NO  = '0';

    /**
     * @ORM\Column(length=100, unique=true)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $fieldAlias;

    /**
     * The different tag that a field can be in a reporting template.
     *
     * @ORM\Column(length=11)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $tag;

    /**
     * The different sections of a reservation.
     *
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $category;

    /**
     * The different data types that a field can be in a program eligibility.
     *
     * @ORM\Column(length=20)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $type;

    /**
     * @ORM\Column(length=255)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $reservationPropertyName;

    /**
     * @ORM\Column(length=255)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $propertyPath;

    /**
     * @ORM\Column(length=255)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $propertyType;

    /**
     * @ORM\Column(length=255)
     */
    private string $objectClass;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private bool $comparable;

    /**
     * If comparable, we need to specify its unit to compare the value of the same unit.
     * It can also be used to build the translation of a unit.
     *
     * @ORM\Column(length=20, nullable=true)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private ?string $unit;

    /**
     * The items of a field of type pre-defined list (and not an user-defined's).
     *
     * @ORM\Column(type="json", nullable=true)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private ?array $predefinedItems;

    public function __construct(
        string $fieldAlias,
        string $tag,
        string $category,
        string $type,
        string $reservationPropertyName,
        string $propertyPath,
        string $propertyType,
        string $objectClass,
        bool $comparable,
        ?string $unit,
        ?array $predefinedItems
    ) {
        $this->fieldAlias              = $fieldAlias;
        $this->tag                     = $tag;
        $this->category                = $category;
        $this->type                    = $type;
        $this->reservationPropertyName = $reservationPropertyName;
        $this->propertyPath            = $propertyPath;
        $this->propertyType            = $propertyType;
        $this->objectClass             = $objectClass;
        $this->comparable              = $comparable;
        $this->unit                    = $unit;
        $this->predefinedItems         = $predefinedItems;
    }

    public function getFieldAlias(): string
    {
        return $this->fieldAlias;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getReservationPropertyName(): string
    {
        return $this->reservationPropertyName;
    }

    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    public function getPropertyType(): string
    {
        return $this->propertyType;
    }

    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    public function isComparable(): bool
    {
        return $this->comparable;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function getPredefinedItems(): ?array
    {
        return $this->predefinedItems;
    }
}
