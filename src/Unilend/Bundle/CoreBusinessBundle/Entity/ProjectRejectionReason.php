<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRejectionReason
 *
 * @ORM\Table(name="project_rejection_reason")
 * @ORM\Entity
 */
class ProjectRejectionReason
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
     * @ORM\Column(name="id_rejection", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRejection;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectRejectionReason
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
     * Get idRejection
     *
     * @return integer
     */
    public function getIdRejection()
    {
        return $this->idRejection;
    }
}
