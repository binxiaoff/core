<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRateSettings
 *
 * @ORM\Table(name="project_rate_settings", indexes={@ORM\Index(name="idx_project_rate_settings_eval_period_status", columns={"status", "evaluation", "id_period"}), @ORM\Index(name="project_rate_settings_project_period_id_period", columns={"id_period"})})
 * @ORM\Entity
 */
class ProjectRateSettings
{
    /**
     * @var string
     *
     * @ORM\Column(name="evaluation", type="string", length=2, nullable=false)
     */
    private $evaluation;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var float
     *
     * @ORM\Column(name="rate_min", type="float", precision=3, scale=1, nullable=false)
     */
    private $rateMin;

    /**
     * @var float
     *
     * @ORM\Column(name="rate_max", type="float", precision=3, scale=1, nullable=false)
     */
    private $rateMax;

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
     * @ORM\Column(name="id_rate", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRate;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectPeriod
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectPeriod")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_period", referencedColumnName="id_period")
     * })
     */
    private $idPeriod;



    /**
     * Set evaluation
     *
     * @param string $evaluation
     *
     * @return ProjectRateSettings
     */
    public function setEvaluation($evaluation)
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    /**
     * Get evaluation
     *
     * @return string
     */
    public function getEvaluation()
    {
        return $this->evaluation;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return ProjectRateSettings
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set rateMin
     *
     * @param float $rateMin
     *
     * @return ProjectRateSettings
     */
    public function setRateMin($rateMin)
    {
        $this->rateMin = $rateMin;

        return $this;
    }

    /**
     * Get rateMin
     *
     * @return float
     */
    public function getRateMin()
    {
        return $this->rateMin;
    }

    /**
     * Set rateMax
     *
     * @param float $rateMax
     *
     * @return ProjectRateSettings
     */
    public function setRateMax($rateMax)
    {
        $this->rateMax = $rateMax;

        return $this;
    }

    /**
     * Get rateMax
     *
     * @return float
     */
    public function getRateMax()
    {
        return $this->rateMax;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectRateSettings
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
     * @return ProjectRateSettings
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
     * Get idRate
     *
     * @return integer
     */
    public function getIdRate()
    {
        return $this->idRate;
    }

    /**
     * Set idPeriod
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectPeriod $idPeriod
     *
     * @return ProjectRateSettings
     */
    public function setIdPeriod(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectPeriod $idPeriod = null)
    {
        $this->idPeriod = $idPeriod;

        return $this;
    }

    /**
     * Get idPeriod
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectPeriod
     */
    public function getIdPeriod()
    {
        return $this->idPeriod;
    }
}
