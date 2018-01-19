<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RiskDataMonitoring
 *
 * @ORM\Table(name="risk_data_monitoring", indexes={@ORM\Index(name="idx_risk_data_monitoring_siren", columns={"siren"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\RiskDataMonitoringRepository")
 * @ORM\HasLifecycleCallbacks
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
     * @ORM\Column(name="provider", type="string", length=191, nullable=false)
     */
    private $provider;

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
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

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
    public function setSiren(string $siren) : RiskDataMonitoring
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * Get siren
     *
     * @return string
     */
    public function getSiren() : string
    {
        return $this->siren;
    }

    /**
     * Set provider
     *
     * @param string $provider
     *
     * @return RiskDataMonitoring
     */
    public function setProvider(string $provider) : RiskDataMonitoring
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get provider
     *
     * @return string
     */
    public function getProvider() : string
    {
        return $this->provider;
    }

    /**
     * Set start
     *
     * @param \DateTime $start
     *
     * @return RiskDataMonitoring
     */
    public function setStart(\DateTime $start) : RiskDataMonitoring
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime
     */
    public function getStart() : \DateTime
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
    public function setEnd(\DateTime $end) : RiskDataMonitoring
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return null|\DateTime
     */
    public function getEnd() : ?\DateTime
    {
        return $this->end;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isOngoing() : bool
    {
        return empty($this->getEnd());
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return RiskDataMonitoring
     */
    public function setAdded(\DateTime $added) : RiskDataMonitoring
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded() : \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return RiskDataMonitoring
     */
    public function setUpdated(?\DateTime $updated) : RiskDataMonitoring
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated() : ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue() : void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue() : void
    {
        $this->updated = new \DateTime();
    }
}
