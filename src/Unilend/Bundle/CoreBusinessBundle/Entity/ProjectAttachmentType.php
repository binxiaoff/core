<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectAttachmentType
 *
 * @ORM\Table(name="project_attachment_type")
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ProjectAttachmentTypeRepository")
 */
class ProjectAttachmentType
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
     * @ORM\Column(name="max_items", type="smallint", nullable=false, options={"default" : 1})
     */
    private $maxItems = 1;

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
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachmentTypeCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_category", referencedColumnName="id")
     * })
     */
    private $idCategory;

    /**
     * @var AttachmentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false, unique=true)
     * })
     */
    private $idType;

    /**
     * Column only used to index results in "getAttachmentTypes" method of repository, no getter/setter necessary
     * @var int
     *
     * @ORM\Column(name="id_type", type="integer")
     */
    private $type;



    /**
     * Set rank
     *
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
     * Get rank
     *
     * @return int
     */
    public function getRank(): int
    {
        return $this->rank;
    }

    /**
     * Set maxItems
     *
     * @param int $maxItems
     *
     * @return ProjectAttachmentType
     */
    public function setMaxItems(int $maxItems): ProjectAttachmentType
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    /**
     * Get maxItems
     *
     * @return int
     */
    public function getMaxItems(): int
    {
        return $this->maxItems;
    }

    /**
     * Set name
     *
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
     * Get name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set idCategory
     *
     * @param ProjectAttachmentTypeCategory|null $idCategory
     *
     * @return ProjectAttachmentType
     */
    public function setIdCategory(?ProjectAttachmentTypeCategory $idCategory): ProjectAttachmentType
    {
        $this->idCategory = $idCategory;

        return $this;
    }

    /**
     * Get idCategory
     *
     * @return ProjectAttachmentTypeCategory|null
     */
    public function getIdCategory(): ?ProjectAttachmentTypeCategory
    {
        return $this->idCategory;
    }

    /**
     * Set idType
     *
     * @param AttachmentType $idType
     *
     * @return ProjectAttachmentType
     */
    public function setIdType(AttachmentType $idType): ProjectAttachmentType
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return AttachmentType
     */
    public function getIdType(): AttachmentType
    {
        return $this->idType;
    }
}
