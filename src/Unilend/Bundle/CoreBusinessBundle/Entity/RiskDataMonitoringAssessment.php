<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RiskDataMonitoringAssessment
 *
 * @ORM\Table(name="risk_data_monitoring_assessment", indexes={@ORM\Index(name="idx_risk_data_monitoring_assess_risk_data_call_log", columns={"id_risk_data_monitoring_call_log"}), @ORM\Index(name="idx_risk_data_monitoring_assess_risk_data_monitoring_type", columns={"id_risk_data_monitoring_type"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class RiskDataMonitoringAssessment
{
    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=191, nullable=false)
     */
    private $value;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRuleSet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project_eligibility_rule_set", referencedColumnName="id")
     * })
     */
    private $idProjectEligibilityRuleSet;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringCallLog
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringCallLog")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_risk_data_monitoring_call_log", referencedColumnName="id")
     * })
     */
    private $idRiskDataMonitoringCallLog;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_risk_data_monitoring_type", referencedColumnName="id")
     * })
     */
    private $idRiskDataMonitoringType;

    /**
     * Set value
     *
     * @param string $value
     *
     * @return RiskDataMonitoringAssessment
     */
    public function setValue(string $value): RiskDataMonitoringAssessment
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Set idProjectEligibilityRuleSet
     *
     * @param null|ProjectEligibilityRuleSet
     *
     * @return RiskDataMonitoringAssessment
     */
    public function setIdProjectEligibilityRuleSet(?ProjectEligibilityRuleSet $idProjectEligibilityRuleSet): RiskDataMonitoringAssessment
    {
        $this->idProjectEligibilityRuleSet = $idProjectEligibilityRuleSet;

        return $this;
    }

    /**
     * Get idProjectEligibilityRuleSet
     *
     * @return ProjectEligibilityRuleSet
     */
    public function getIdProjectEligibilityRuleSet(): ProjectEligibilityRuleSet
    {
        return $this->idProjectEligibilityRuleSet;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return RiskDataMonitoringAssessment
     */
    public function setAdded(\DateTime $added): RiskDataMonitoringAssessment
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
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set idRiskDataMonitoringCallLog
     *
     * @param RiskDataMonitoringCallLog $idRiskDataMonitoringCallLog
     *
     * @return RiskDataMonitoringAssessment
     */
    public function setIdRiskDataMonitoringCallLog(?RiskDataMonitoringCallLog $idRiskDataMonitoringCallLog): RiskDataMonitoringAssessment
    {
        $this->idRiskDataMonitoringCallLog = $idRiskDataMonitoringCallLog;

        return $this;
    }

    /**
     * Get idRiskDataMonitoringCallLog
     *
     * @return RiskDataMonitoringCallLog
     */
    public function getIdRiskDataMonitoringCallLog(): RiskDataMonitoringCallLog
    {
        return $this->idRiskDataMonitoringCallLog;
    }

    /**
     * Set idRiskDataMonitoringType
     *
     * @param RiskDataMonitoringType $idRiskDataMonitoringType
     *
     * @return RiskDataMonitoringAssessment
     */
    public function setIdRiskDataMonitoringType(RiskDataMonitoringType $idRiskDataMonitoringType): RiskDataMonitoringAssessment
    {
        $this->idRiskDataMonitoringType = $idRiskDataMonitoringType;

        return $this;
    }

    /**
     * Get idRiskDataMonitoringType
     *
     * @return RiskDataMonitoringType
     */
    public function getIdRiskDataMonitoringType(): RiskDataMonitoringType
    {
        return $this->idRiskDataMonitoringType;
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
}
