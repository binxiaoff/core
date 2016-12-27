<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Bids
 *
 * @ORM\Table(name="bids", indexes={@ORM\Index(name="id_lender_account", columns={"id_lender_account"}), @ORM\Index(name="idprojectstatus", columns={"id_project", "status"}), @ORM\Index(name="idx_bids_id_lender_wallet_line", columns={"id_lender_wallet_line"}), @ORM\Index(name="idx_id_autobid", columns={"id_autobid"})})
 * @ORM\Entity
 */
class Bids
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender_account", type="integer", nullable=false)
     */
    private $idLenderAccount;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_autobid", type="integer", nullable=false)
     */
    private $idAutobid;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender_wallet_line", type="integer", nullable=false)
     */
    private $idLenderWalletLine;

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
     * @var integer
     *
     * @ORM\Column(name="checked", type="integer", nullable=false)
     */
    private $checked;

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
     * Set idLenderAccount
     *
     * @param integer $idLenderAccount
     *
     * @return Bids
     */
    public function setIdLenderAccount($idLenderAccount)
    {
        $this->idLenderAccount = $idLenderAccount;

        return $this;
    }

    /**
     * Get idLenderAccount
     *
     * @return integer
     */
    public function getIdLenderAccount()
    {
        return $this->idLenderAccount;
    }

    /**
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return Bids
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return integer
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set idAutobid
     *
     * @param integer $idAutobid
     *
     * @return Bids
     */
    public function setIdAutobid($idAutobid)
    {
        $this->idAutobid = $idAutobid;

        return $this;
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

    /**
     * Set idLenderWalletLine
     *
     * @param integer $idLenderWalletLine
     *
     * @return Bids
     */
    public function setIdLenderWalletLine($idLenderWalletLine)
    {
        $this->idLenderWalletLine = $idLenderWalletLine;

        return $this;
    }

    /**
     * Get idLenderWalletLine
     *
     * @return integer
     */
    public function getIdLenderWalletLine()
    {
        return $this->idLenderWalletLine;
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
     * Set checked
     *
     * @param integer $checked
     *
     * @return Bids
     */
    public function setChecked($checked)
    {
        $this->checked = $checked;

        return $this;
    }

    /**
     * Get checked
     *
     * @return integer
     */
    public function getChecked()
    {
        return $this->checked;
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
}
