<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

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
    private string $targetPropertyAccessPath;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private bool $comparable;

    /**
     * If the filed is a predefined list, we put its items here
     *
     * @ORM\Column(type="json", nullable=true)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private ?array $predefinedItems;

    /**
     * If comparable, what is its unit is. We can only compare the value of the same unit. It can also be used to build the translation of a unit.
     *
     * @ORM\Column(length=20, nullable=true)
     *
     * @Groups({"creditGuaranty:field:read"})
     */
    private ?string $unit;

    /**
     * @param string      $fieldAlias
     * @param string      $category
     * @param string      $type
     * @param string      $targetPropertyAccessPath
     * @param bool        $comparable
     * @param string|null $unit
     * @param array|null  $predefinedItems
     */
    public function __construct(string $fieldAlias, string $category, string $type, string $targetPropertyAccessPath, bool $comparable, ?string $unit, ?array $predefinedItems)
    {
        $this->fieldAlias               = $fieldAlias;
        $this->category                 = $category;
        $this->type                     = $type;
        $this->targetPropertyAccessPath = $targetPropertyAccessPath;
        $this->comparable               = $comparable;
        $this->unit                     = $unit;
        $this->predefinedItems          = $predefinedItems;
    }

    /**
     * @return string
     */
    public function getFieldAlias(): string
    {
        return $this->fieldAlias;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTargetPropertyAccessPath(): string
    {
        return $this->targetPropertyAccessPath;
    }

    /**
     * @return bool
     */
    public function isComparable(): bool
    {
        return $this->comparable;
    }

    /**
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * @return array|null
     */
    public function getPredefinedItems(): ?array
    {
        return $this->predefinedItems;
    }
}
