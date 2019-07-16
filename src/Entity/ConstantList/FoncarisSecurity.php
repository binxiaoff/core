<?php

namespace Unilend\Entity\ConstantList;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\ConstantList\FoncarisSecurityRepository")
 */
class FoncarisSecurity
{
    public const CATEGORY_ID_SIGNATURE_COMMITMENTS = 1;
    public const CATEGORY_ID_ASSIGNMENT_OF_CLAIM   = 2;
    public const CATEGORY_ID_MORTGAGE              = 3;
    public const CATEGORY_ID_COLLATERAL_PLEDGE     = 4;

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
     * @return FoncarisSecurity
     */
    public function setCategory(int $category): FoncarisSecurity
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
     * @return FoncarisSecurity
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
