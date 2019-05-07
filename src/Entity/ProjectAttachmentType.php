<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Interfaces\EntityAttachmentTypeCategoryInterface;
use Unilend\Entity\Interfaces\EntityAttachmentTypeInterface;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectAttachmentTypeRepository")
 */
class ProjectAttachmentType implements EntityAttachmentTypeInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="rank", type="smallint")
     */
    private $rank;

    /**
     * @var int
     *
     * @ORM\Column(name="max_items", type="smallint", nullable=true)
     */
    private $maxItems;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=true)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var ProjectAttachmentTypeCategory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectAttachmentTypeCategory")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_category", referencedColumnName="id")
     * })
     */
    private $category;

    /**
     * @var AttachmentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\attachmentType")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false, unique=true)
     * })
     */
    private $attachmentType;

    /**
     * @param int $rank
     *
     * @return ProjectAttachmentType
     */
    public function setRank(int $rank): ProjectAttachmentType
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
     * @param int|null $maxItems
     *
     * @return ProjectAttachmentType
     */
    public function setMaxItems(?int $maxItems): ProjectAttachmentType
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    /**
     * @param string|null $name
     *
     * @return ProjectAttachmentType
     */
    public function setName(?string $name): ProjectAttachmentType
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param ProjectAttachmentTypeCategory|null $category
     *
     * @return ProjectAttachmentType
     */
    public function setCategory(?ProjectAttachmentTypeCategory $category): ProjectAttachmentType
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return EntityAttachmentTypeCategoryInterface|null
     */
    public function getCategory(): ?EntityAttachmentTypeCategoryInterface
    {
        return $this->category;
    }

    /**
     * @param AttachmentType $attachmentType
     *
     * @return ProjectAttachmentType
     */
    public function setAttachmentType(AttachmentType $attachmentType): ProjectAttachmentType
    {
        $this->attachmentType = $attachmentType;

        return $this;
    }

    /**
     *
     * @return AttachmentType
     */
    public function getAttachmentType(): AttachmentType
    {
        return $this->attachmentType;
    }
}
