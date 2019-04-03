<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectAttachmentTypeCategory
 *
 * @ORM\Table(name="project_attachment_type_category")
 * @ORM\Entity
 */
class ProjectAttachmentTypeCategory
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
     * Set label
     *
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
     * Get label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Set name
     *
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
     * Get name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set rank
     *
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
     * Get rank
     *
     * @return int
     */
    public function getRank(): int
    {
        return $this->rank;
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
}
