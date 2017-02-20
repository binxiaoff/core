<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VigilanceRuleDetail
 *
 * @ORM\Table(name="vigilance_rule_detail", indexes={@ORM\Index(name="fk_vigilance_rule_vigilance_rule_details_id_rule", columns={"id_rule"})})
 * @ORM\Entity
 */
class VigilanceRuleDetail
{
    /**
     * @var string
     *
     * @ORM\Column(name="action_label", type="string", length=191, nullable=false)
     */
    private $actionLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="period", type="string", length=16, nullable=true)
     */
    private $period;

    /**
     * @var integer
     *
     * @ORM\Column(name="number", type="integer", nullable=true)
     */
    private $number;

    /**
     * @var boolean
     *
     * @ORM\Column(name="number_type", type="boolean", nullable=true)
     */
    private $numberType;

    /**
     * @var string
     *
     * @ORM\Column(name="comparison_operator", type="string", length=3, nullable=true)
     */
    private $comparisonOperator;

    /**
     * @var string
     *
     * @ORM\Column(name="client_type", type="string", length=15, nullable=true)
     */
    private $clientType;

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
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_rule_detail", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRuleDetail;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule", referencedColumnName="id")
     * })
     */
    private $rule;

    /**
     * @return string
     */
    public function getActionLabel()
    {
        return $this->actionLabel;
    }

    /**
     * @param string $actionLabel
     *
     * @return VigilanceRuleDetail
     */
    public function setActionLabel($actionLabel)
    {
        $this->actionLabel = $actionLabel;

        return $this;
    }

    /**
     * @return string
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param string $period
     *
     * @return VigilanceRuleDetail
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     *
     * @return VigilanceRuleDetail
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberType()
    {
        return $this->numberType;
    }

    /**
     * @param int $numberType
     *
     * @return VigilanceRuleDetail
     */
    public function setNumberType($numberType)
    {
        $this->numberType = $numberType;

        return $this;
    }

    /**
     * @return string
     */
    public function getComparisonOperator()
    {
        return $this->comparisonOperator;
    }

    /**
     * @param string $comparisonOperator
     *
     * @return VigilanceRuleDetail
     */
    public function setComparisonOperator($comparisonOperator)
    {
        $this->comparisonOperator = $comparisonOperator;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientType()
    {
        return $this->clientType;
    }

    /**
     * @param string $clientType
     *
     * @return VigilanceRuleDetail
     */
    public function setClientType($clientType)
    {
        $this->clientType = $clientType;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param \DateTime $added
     *
     * @return VigilanceRuleDetail
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     *
     * @return VigilanceRuleDetail
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * @param int $idUser
     *
     * @return VigilanceRuleDetail
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdRuleDetail()
    {
        return $this->idRuleDetail;
    }

    /**
     * @param int $idRuleDetail
     *
     * @return VigilanceRuleDetail
     */
    public function setIdRuleDetail($idRuleDetail)
    {
        $this->idRuleDetail = $idRuleDetail;

        return $this;
    }

    /**
     * @return VigilanceRule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param VigilanceRule $rule
     *
     * @return VigilanceRuleDetail
     */
    public function setRule(VigilanceRule $rule)
    {
        $this->rule = $rule;

        return $this;
    }
}
