<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\ConstantList;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\IdentityTrait;

class EligibilityCondition
{
    use IdentityTrait;

    private const TYPE_LIST = 'list';
    private const TYPE_BOOL = 'bool';
    private const TYPE_DATA = 'data';

    /**
     * @ORM\Column(length=100)
     */
    private string $name;

    /**
     * @ORM\Column(length=100)
     */
    private string $category;

    /**
     * @ORM\Column(length=20)
     */
    private string $type;

    /**
     * @param string $name
     * @param string $category
     * @param string $type
     */
    public function __construct(string $name, string $category, string $type)
    {
        $this->name     = $name;
        $this->category = $category;
        $this->type     = $type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
}
