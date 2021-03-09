<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\ConstantList;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     itemOperations={"get"},
 *     collectionOperations={"get"}
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_eligibility_criteria")
 */
class EligibilityCriteria
{
    use PublicizeIdentityTrait;

    // The criteria contains a list of items defined later by the user. The eligibility is configured on each item.
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
     * @Groups({"creditGuaranty:eligibilityCriteria:read"})
     */
    private string $fieldAlias;

    /**
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:eligibilityCriteria:read"})
     */
    private string $category;

    /**
     * @ORM\Column(length=20)
     *
     * @Groups({"creditGuaranty:eligibilityCriteria:read"})
     */
    private string $type;

    /**
     * @ORM\Column(length=255)
     */
    private string $targetPropertyAccessPath;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"creditGuaranty:eligibilityCriteria:read"})
     */
    private bool $comparable;

    /**
     * If comparable, what is its unit is. We can only compare the value of the same unit. It can also be used to build the translation of a unit.
     *
     * @ORM\Column(length=20, nullable=true)
     *
     * @Groups({"creditGuaranty:eligibilityCriteria:read"})
     */
    private ?string $unit;

    /**
     * @param string      $fieldAlias
     * @param string      $category
     * @param string      $type
     * @param string      $targetPropertyAccessPath
     * @param bool        $comparable
     * @param string|null $unit
     */
    public function __construct(string $fieldAlias, string $category, string $type, string $targetPropertyAccessPath, bool $comparable, ?string $unit = null)
    {
        $this->fieldAlias               = $fieldAlias;
        $this->category                 = $category;
        $this->type                     = $type;
        $this->targetPropertyAccessPath = $targetPropertyAccessPath;
        $this->comparable               = $comparable;
        $this->unit                     = $unit;
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
}
