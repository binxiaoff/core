<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Autobid
 *
 * @ORM\Table(name="autobid", indexes={@ORM\Index(name="idx_autobid_eval_period", columns={"evaluation", "id_period", "status"}), @ORM\Index(name="idx_autobid_id_lender", columns={"id_lender"})})
 * @ORM\Entity
 */
class Autobid
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender", type="integer", nullable=false)
     */
    private $idLender;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="evaluation", type="string", length=2, nullable=false)
     */
    private $evaluation;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_period", type="integer", nullable=false)
     */
    private $idPeriod;

    /**
     * @var float
     *
     * @ORM\Column(name="rate_min", type="float", precision=3, scale=1, nullable=false)
     */
    private $rateMin;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

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
     * @ORM\Column(name="id_autobid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAutobid;



    /**
     * Set idLender
     *
     * @param integer $idLender
     *
     * @return Autobid
     */
    public function setIdLender($idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return integer
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Autobid
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
     * Set evaluation
     *
     * @param string $evaluation
     *
     * @return Autobid
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
     * Set idPeriod
     *
     * @param integer $idPeriod
     *
     * @return Autobid
     */
    public function setIdPeriod($idPeriod)
    {
        $this->idPeriod = $idPeriod;

        return $this;
    }

    /**
     * Get idPeriod
     *
     * @return integer
     */
    public function getIdPeriod()
    {
        return $this->idPeriod;
    }

    /**
     * Set rateMin
     *
     * @param float $rateMin
     *
     * @return Autobid
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
     * Set amount
     *
     * @param integer $amount
     *
     * @return Autobid
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Autobid
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
     * @return Autobid
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
     * Get idAutobid
     *
     * @return integer
     */
    public function getIdAutobid()
    {
        return $this->idAutobid;
    }
}
