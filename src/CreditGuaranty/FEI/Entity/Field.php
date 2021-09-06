<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     itemOperations={"get"},
 *     collectionOperations={"get"}
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_field")
 */
class Field
{
    use PublicizeIdentityTrait;

    // The criteria contains a list of items pre-defined or defined later by the user. The eligibility is configured on each item.
    public const TYPE_LIST = 'list';
    // It is a special version of "list". The listed items are booleans (yes or no).
    public const TYPE_BOOL = 'bool';
    // Other type that we haven't yet managed.
    public const TYPE_OTHER = 'other';

    public const VALUE_BOOL_YES = '1';
    public const VALUE_BOOL_NO  = '0';

    /**
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $fieldAlias;

    /**
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $category;

    /**
     * @ORM\Column(length=20)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private string $type;

    /**
     * @ORM\Column(length=255)
     */
    private string $reservationPropertyName;

    /**
     * @ORM\Column(length=255)
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
     * If the field is a pre-defined list (and not an user-defined's), we store its items here.
     *
     * @ORM\Column(type="json", nullable=true)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private ?array $predefinedItems;

    /**
     * If comparable, we need to specify its unit to compare the value of the same unit.
     * It can also be used to build the translation of a unit.
     *
     * @ORM\Column(length=20, nullable=true)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private ?string $unit;

    public function __construct(
        string $fieldAlias,
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
