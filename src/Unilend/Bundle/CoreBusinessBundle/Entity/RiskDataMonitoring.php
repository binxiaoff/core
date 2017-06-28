<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RiskDataMonitoring
 *
 * @ORM\Table(name="risk_data_monitoring", indexes={@ORM\Index(name="idx_risk_data_monitoring_siren", columns={"siren"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\RiskDataMonitoringRepository")
 */
class RiskDataMonitoring
{
    /**
     * @var string
     *
     * @ORM\Column(name="siren", type="string", length=14, nullable=false)
     */
    private $siren;

    /**
     * @var string
     *
     * @ORM\Column(name="rating_type", type="string", length=191, nullable=true)
     */
    private $ratingType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start", type="datetime", nullable=false)
     */
    private $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end", type="datetime", nullable=true)
     */
    private $end;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Set siren
     *
     * @param string $siren
     *
     * @return RiskDataMonitoring
     */
    public function setSiren($siren)
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * Get siren
     *
     * @return string
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * Set ratingType
     *
     * @param string $ratingType
     *
     * @return RiskDataMonitoring
     */
    public function setRatingType($ratingType)
    {
        $this->ratingType = $ratingType;

        return $this;
    }

    /**
     * Get ratingType
     *
     * @return string
     */
    public function getRatingType()
    {
        return $this->ratingType;
    }

    /**
     * Set start
     *
     * @param \DateTime $start
     *
     * @return RiskDataMonitoring
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     *
     * @return RiskDataMonitoring
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isOngoing()
    {
        return empty($this->getEnd());
    }
}
