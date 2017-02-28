<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UnilendStats
 *
 * @ORM\Table(name="unilend_stats")
 * @ORM\Entity
 */
class UnilendStats
{
    /**
     * @var string
     *
     * @ORM\Column(name="type_stat", type="string", length=191, nullable=false)
     */
    private $typeStat;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", length=65535, nullable=false)
     */
    private $value;

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
     * @ORM\Column(name="id_unilend_stat", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idUnilendStat;



    /**
     * Set typeStat
     *
     * @param string $typeStat
     *
     * @return UnilendStats
     */
    public function setTypeStat($typeStat)
    {
        $this->typeStat = $typeStat;

        return $this;
    }

    /**
     * Get typeStat
     *
     * @return string
     */
    public function getTypeStat()
    {
        return $this->typeStat;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return UnilendStats
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return UnilendStats
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
     * @return UnilendStats
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
     * Get idUnilendStat
     *
     * @return integer
     */
    public function getIdUnilendStat()
    {
        return $this->idUnilendStat;
    }
}
