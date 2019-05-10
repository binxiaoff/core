<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Interfaces\EntityAttachmentTypeCategoryInterface;

/**
 * @ORM\Entity
 */
class ProjectAttachmentTypeCategory implements EntityAttachmentTypeCategoryInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, unique=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=true)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="rank", type="smallint")
     */
    private $rank;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @param string $label
     *
     * @return ProjectAttachmentTypeCategory
     */
    public function setLabel(string $label): ProjectAttachmentTypeCategory
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string|null $name
     *
     * @return ProjectAttachmentTypeCategory
     */
    public function setName(?string $name): ProjectAttachmentTypeCategory
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param int $rank
     *
     * @return ProjectAttachmentTypeCategory
     */
    public function setRank(int $rank): ProjectAttachmentTypeCategory
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * @return int
     */
    public function getRank(): int
    {
        return $this->rank;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
