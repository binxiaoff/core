<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UnilendStats
 *
 * @ORM\Table(name="unilend_stats")
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\UnilendStatsRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class UnilendStats
{
    const TYPE_STAT_IRR               = 'IRR';
    const TYPE_STAT_MAX_IRR           = 'max_IRR';
    const TYPE_STAT_FRONT_STATISTIC   = 'unilend_front_statistics';
    const TYPE_FPF_FRONT_STATISTIC    = 'fpf_front_statistic';
    const TYPE_INCIDENCE_RATE         = 'incidence_rate';
    const TYPE_QUARTER_INCIDENCE_RATE = 'quarter_incidence_rate';

    const DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS = 180;

    /**
     * @var string
     *
     * @ORM\Column(name="type_stat", type="string", length=191)
     */
    private $typeStat;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", length=65535)
     */
    private $value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
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

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
