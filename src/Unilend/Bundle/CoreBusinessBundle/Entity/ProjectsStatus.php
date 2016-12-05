<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsStatus
 *
 * @ORM\Table(name="projects_status", uniqueConstraints={@ORM\UniqueConstraint(name="status", columns={"status"})})
 * @ORM\Entity
 */
class ProjectsStatus
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project_status", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectStatus;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectsStatus
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return ProjectsStatus
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get idProjectStatus
     *
     * @return integer
     */
    public function getIdProjectStatus()
    {
        return $this->idProjectStatus;
    }
}
