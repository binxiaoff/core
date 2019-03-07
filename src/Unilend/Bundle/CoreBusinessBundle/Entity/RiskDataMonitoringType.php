<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RiskDataMonitoringType
 *
 * @ORM\Table(name="risk_data_monitoring_type", indexes={@ORM\Index(name="idx_risk_data_monitoring_type_provider", columns={"provider"})})
 * @ORM\Entity
 */
class RiskDataMonitoringType
{
    /**
     * @var string
     *
     * @ORM\Column(name="provider", type="string", length=191)
     */
    private $provider;

    /**
     * @var string
     *
     * @ORM\Column(name="company_rating", type="string", length=191, nullable=true, unique=true)
     */
    private $companyRating;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project_eligibility_rule", referencedColumnName="id")
     * })
     */
    private $idProjectEligibilityRule;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Set provider
     *
     * @param string $provider
     *
     * @return RiskDataMonitoringType
     */
    public function setProvider(string $provider): RiskDataMonitoringType
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Set CompanyRating
     *
     * @param string $companyRating
     *
     * @return RiskDataMonitoringType
     */
    public function setCompanyRating(?string $companyRating): RiskDataMonitoringType
    {
        $this->companyRating = $companyRating;

        return $this;
    }

    /**
     * Get CompanyRating
     *
     * @return string
     */
    public function getCompanyRating(): string
    {
        return $this->companyRating;
    }

    /**
     * Set idProjectEligibilityRule
     *
     * @param null|ProjectEligibilityRule $idProjectEligibilityRule
     *
     * @return RiskDataMonitoringType
     */
    public function setIdProjectEligibilityRule(?ProjectEligibilityRule $idProjectEligibilityRule): RiskDataMonitoringType
    {
        $this->idProjectEligibilityRule = $idProjectEligibilityRule;

        return $this;
    }

    /**
     * Get idProjectEligibilityRule
     *
     * @return ProjectEligibilityRule|null
     */
    public function getIdProjectEligibilityRule(): ?ProjectEligibilityRule
    {
        return $this->idProjectEligibilityRule;
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
}
