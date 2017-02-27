<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectNeed
 *
 * @ORM\Table(name="project_need")
 * @ORM\Entity
 */
class ProjectNeed
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_parent", type="integer", nullable=false)
     */
    private $idParent = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="rank", type="integer", nullable=false)
     */
    private $rank;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project_need", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectNeed;



    /**
     * Set idParent
     *
     * @param integer $idParent
     *
     * @return ProjectNeed
     */
    public function setIdParent($idParent)
    {
        $this->idParent = $idParent;

        return $this;
    }

    /**
     * Get idParent
     *
     * @return integer
     */
    public function getIdParent()
    {
        return $this->idParent;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectNeed
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
     * Set rank
     *
     * @param integer $rank
     *
     * @return ProjectNeed
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectNeed
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return ProjectNeed
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idProjectNeed
     *
     * @return integer
     */
    public function getIdProjectNeed()
    {
        return $this->idProjectNeed;
    }
}
