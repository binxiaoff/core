<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectAbandonReason
 *
 * @ORM\Table(name="project_abandon_reason")
 * @ORM\Entity
 */
class ProjectAbandonReason
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
     * @ORM\Column(name="id_abandon", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAbandon;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectAbandonReason
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
     * Get idAbandon
     *
     * @return integer
     */
    public function getIdAbandon()
    {
        return $this->idAbandon;
    }
}
