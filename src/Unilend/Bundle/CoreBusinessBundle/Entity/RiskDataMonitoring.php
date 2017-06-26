<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RiskDataMonitoring
 *
 * @ORM\Table(name="risk_data_monitoring", indexes={@ORM\Index(name="idx_risk_data_monitoring_id_company", columns={"id_company"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\RiskDataMonitoringRepository")
 */
class RiskDataMonitoring
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=true)
     */
    private $idClient;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company")
     * })
     */
    private $idCompany;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return RiskDataMonitoring
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
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
     * Set idCompany
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Companies $idCompany
     *
     * @return RiskDataMonitoring
     */
    public function setIdCompany($idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Companies
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * @return bool
     */
    public function isOngoing()
    {
        return empty($this->getEnd());
    }
}
