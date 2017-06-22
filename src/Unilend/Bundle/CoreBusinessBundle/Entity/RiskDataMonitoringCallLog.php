<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RiskDataMonitoringCallLog
 *
 * @ORM\Table(name="risk_data_monitoring_call_log", indexes={@ORM\Index(name="idx_risk_data_monitoring_call_log_risk_data_monitoring", columns={"id_risk_data_monitoring"}), @ORM\Index(name="idx_risk_data_monitoring_call_log_company_rating_history", columns={"id_company_rating_history"})})
 * @ORM\Entity
 */
class RiskDataMonitoringCallLog
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_risk_data_monitoring", referencedColumnName="id")
     * })
     */
    private $idRiskDataMonitoring;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory")
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idRiskDataMonitoring
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring $idRiskDataMonitoring
     *
     * @return RiskDataMonitoringCallLog
     */
    public function setIdRiskDataMonitoring(RiskDataMonitoring $idRiskDataMonitoring)
    {
        $this->idRiskDataMonitoring = $idRiskDataMonitoring;

        return $this;
    }

    /**
     * Get idRiskDataMonitoring
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring
     */
    public function getIdRiskDataMonitoring()
    {
        return $this->idRiskDataMonitoring;
    }

    /**
     * Set idCompanyRatingHistory
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory $idCompanyRatingHistory
     *
     * @return RiskDataMonitoringCallLog
     */
    public function setIdCompanyRatingHistory(CompanyRatingHistory $idCompanyRatingHistory)
    {
        $this->idCompanyRatingHistory = $idCompanyRatingHistory;

        return $this;
    }

    /**
     * Get idCompanyRatingHistory
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory
     */
    public function getIdCompanyRatingHistory()
    {
        return $this->idCompanyRatingHistory;
    }
}
