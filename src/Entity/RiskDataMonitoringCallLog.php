<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RiskDataMonitoringCallLog
 *
 * @ORM\Table(name="risk_data_monitoring_call_log", indexes={@ORM\Index(name="idx_risk_data_monitoring_call_log_risk_data_monitoring", columns={"id_risk_data_monitoring"}), @ORM\Index(name="idx_risk_data_monitoring_call_log_company_rating_history", columns={"id_company_rating_history"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\RiskDataMonitoringCallLogRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RiskDataMonitoringCallLog
{
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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\RiskDataMonitoring
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\RiskDataMonitoring")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_risk_data_monitoring", referencedColumnName="id", nullable=false)
     * })
     */
    private $idRiskDataMonitoring;

    /**
     * @var \Unilend\Entity\CompanyRatingHistory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\CompanyRatingHistory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company_rating_history", referencedColumnName="id_company_rating_history")
     * })
     */
    private $idCompanyRatingHistory;



    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return RiskDataMonitoringCallLog
     */
    public function setAdded(\DateTime $added): RiskDataMonitoringCallLog
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return RiskDataMonitoringCallLog
     */
    public function setUpdated(?\DateTime $updated): RiskDataMonitoringCallLog
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set idRiskDataMonitoring
     *
     * @param RiskDataMonitoring $idRiskDataMonitoring
     *
     * @return RiskDataMonitoringCallLog
     */
    public function setIdRiskDataMonitoring(RiskDataMonitoring $idRiskDataMonitoring): RiskDataMonitoringCallLog
    {
        $this->idRiskDataMonitoring = $idRiskDataMonitoring;

        return $this;
    }

    /**
     * Get idRiskDataMonitoring
     *
     * @return RiskDataMonitoring
     */
    public function getIdRiskDataMonitoring(): RiskDataMonitoring
    {
        return $this->idRiskDataMonitoring;
    }

    /**
     * Set idCompanyRatingHistory
     *
     * @param CompanyRatingHistory $idCompanyRatingHistory
     *
     * @return RiskDataMonitoringCallLog
     */
    public function setIdCompanyRatingHistory(CompanyRatingHistory $idCompanyRatingHistory): RiskDataMonitoringCallLog
    {
        $this->idCompanyRatingHistory = $idCompanyRatingHistory;

        return $this;
    }

    /**
     * Get idCompanyRatingHistory
     *
     * @return CompanyRatingHistory
     */
    public function getIdCompanyRatingHistory(): CompanyRatingHistory
    {
        return $this->idCompanyRatingHistory;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
