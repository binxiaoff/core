<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Bids
 *
 * @ORM\Table(name="bids", indexes={@ORM\Index(name="id_lender_account", columns={"id_lender_account"}), @ORM\Index(name="idprojectstatus", columns={"id_project", "status"}), @ORM\Index(name="idx_id_autobid", columns={"id_autobid"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\BidsRepository")
 */
class Bids
{
    const STATUS_BID_PENDING                  = 0;
    const STATUS_BID_ACCEPTED                 = 1;
    const STATUS_BID_REJECTED                 = 2;
    const STATUS_AUTOBID_REJECTED_TEMPORARILY = 3;


    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Autobid
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Autobid")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_autobid", referencedColumnName="id_autobid")
     * })
     */
    private $idAutobid;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", precision=10, scale=0, nullable=false)
     */
    private $rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

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
     * @ORM\Column(name="id_bid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBid;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_account", referencedColumnName="id")
     * })
     */
    private $idLenderAccount;

    /**
     * Set Project
     *
     * @param Projects $project
     *
     * @return Bids
     */
    public function setProject(Projects $project = null)
    {
        $this->idProject = $project;

        return $this;
    }

    /**
     * Get Project
     *
     * @return Projects
     */
    public function getProject()
    {
        return $this->idProject;
    }

    /**
     * Set Autobid
     *
     * @param Autobid $autobid
     *
     * @return Bids
     */
    public function setAutobid(Autobid $autobid = null)
    {
        $this->idAutobid = $autobid;

        return $this;
    }

    /**
     * Get Autobid
     *
     * @return Autobid
     */
    public function getAutobid()
    {
        return $this->idAutobid;
    }

    /**
     * Set idLenderAccount
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idLenderAccount
     *
     * @return Bids
     */
    public function setIdLenderAccount(Wallet $idLenderAccount)
    {
        $this->idLenderAccount = $idLenderAccount;

        return $this;
    }

    /**
     * Get idLenderAccount
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdLenderAccount()
    {
        return $this->idLenderAccount;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return Bids
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
     * Set rate
     *
     * @param float $rate
     *
     * @return Bids
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return Bids
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Bids
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Bids
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
     * @return Bids
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
     * Get idBid
     *
     * @return integer
     */
    public function getIdBid()
    {
        return $this->idBid;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
