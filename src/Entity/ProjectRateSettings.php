<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRateSettings
 *
 * @ORM\Table(name="project_rate_settings", indexes={@ORM\Index(name="idx_project_rate_settings_eval_period_status", columns={"status", "evaluation", "id_period"}), @ORM\Index(name="project_rate_settings_project_period_id_period", columns={"id_period"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProjectRateSettingsRepository")
 */
class ProjectRateSettings
{
    const STATUS_ACTIVE   = 1;
    const STATUS_ARCHIVED = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="evaluation", type="string", length=2)
     */
    private $evaluation;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var float
     *
     * @ORM\Column(name="rate_min", type="decimal", precision=3, scale=1)
     */
    private $rateMin;

    /**
     * @var float
     *
     * @ORM\Column(name="rate_max", type="decimal", precision=3, scale=1)
     */
    private $rateMax;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_rate", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRate;

    /**
     * @var \Unilend\Entity\ProjectPeriod
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectPeriod")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_period", referencedColumnName="id_period", nullable=false)
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
     * @param integer $status
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
     * @return integer
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
     * @param \Unilend\Entity\ProjectPeriod $idPeriod
     *
     * @return ProjectRateSettings
     */
    public function setIdPeriod(\Unilend\Entity\ProjectPeriod $idPeriod = null)
    {
        $this->idPeriod = $idPeriod;

        return $this;
    }

    /**
     * Get idPeriod
     *
     * @return \Unilend\Entity\ProjectPeriod
     */
    public function getIdPeriod()
    {
        return $this->idPeriod;
    }
}
