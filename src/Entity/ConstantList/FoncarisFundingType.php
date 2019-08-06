<?php

declare(strict_types=1);

namespace Unilend\Entity\ConstantList;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class FoncarisFundingType
{
    public const CATEGORY_ID_SIGNATURE_COMMITMENTS = 1;
    public const CATEGORY_ID_SHORT_TERM            = 2;
    public const CATEGORY_ID_MEDIUM_LONG_TERM      = 3;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(length=100)
     */
    private $description;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCategory(): int
    {
        return $this->category;
    }

    /**
     * @param int $category
     *
     * @return FoncarisFundingType
     */
    public function setCategory(int $category): FoncarisFundingType
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return FoncarisFundingType
     */
    public function setDescription(string $description): FoncarisFundingType
    {
        $this->description = $description;

        return $this;
    }
}
