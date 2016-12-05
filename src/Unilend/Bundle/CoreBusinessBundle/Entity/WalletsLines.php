<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WalletsLines
 *
 * @ORM\Table(name="wallets_lines", indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="id_transaction", columns={"id_transaction"}), @ORM\Index(name="idx_wallets_lines_id_lender_display", columns={"id_lender", "display"})})
 * @ORM\Entity
 */
class WalletsLines
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender", type="integer", nullable=false)
     */
    private $idLender;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_company", type="integer", nullable=false)
     */
    private $idCompany;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_financial_operation", type="integer", nullable=false)
     */
    private $typeFinancialOperation;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_transaction", type="integer", nullable=false)
     */
    private $idTransaction;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_bid_remb", type="integer", nullable=false)
     */
    private $idBidRemb;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_term_of_loan", type="integer", nullable=false)
     */
    private $idTermOfLoan;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_loan", type="integer", nullable=false)
     */
    private $idLoan;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_term_for_company", type="integer", nullable=false)
     */
    private $idTermForCompany;

    /**
     * @var boolean
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display", type="boolean", nullable=false)
     */
    private $display;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
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
     * @ORM\Column(name="id_wallet_line", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idWalletLine;



    /**
     * Set idLender
     *
     * @param integer $idLender
     *
     * @return WalletsLines
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
     * Set idCompany
     *
     * @param integer $idCompany
     *
     * @return WalletsLines
     */
    public function setIdCompany($idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return integer
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * Set typeFinancialOperation
     *
     * @param integer $typeFinancialOperation
     *
     * @return WalletsLines
     */
    public function setTypeFinancialOperation($typeFinancialOperation)
    {
        $this->typeFinancialOperation = $typeFinancialOperation;

        return $this;
    }

    /**
     * Get typeFinancialOperation
     *
     * @return integer
     */
    public function getTypeFinancialOperation()
    {
        return $this->typeFinancialOperation;
    }

    /**
     * Set idTransaction
     *
     * @param integer $idTransaction
     *
     * @return WalletsLines
     */
    public function setIdTransaction($idTransaction)
    {
        $this->idTransaction = $idTransaction;

        return $this;
    }

    /**
     * Get idTransaction
     *
     * @return integer
     */
    public function getIdTransaction()
    {
        return $this->idTransaction;
    }

    /**
     * Set idBidRemb
     *
     * @param integer $idBidRemb
     *
     * @return WalletsLines
     */
    public function setIdBidRemb($idBidRemb)
    {
        $this->idBidRemb = $idBidRemb;

        return $this;
    }

    /**
     * Get idBidRemb
     *
     * @return integer
     */
    public function getIdBidRemb()
    {
        return $this->idBidRemb;
    }

    /**
     * Set idTermOfLoan
     *
     * @param integer $idTermOfLoan
     *
     * @return WalletsLines
     */
    public function setIdTermOfLoan($idTermOfLoan)
    {
        $this->idTermOfLoan = $idTermOfLoan;

        return $this;
    }

    /**
     * Get idTermOfLoan
     *
     * @return integer
     */
    public function getIdTermOfLoan()
    {
        return $this->idTermOfLoan;
    }

    /**
     * Set idLoan
     *
     * @param integer $idLoan
     *
     * @return WalletsLines
     */
    public function setIdLoan($idLoan)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return integer
     */
    public function getIdLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return WalletsLines
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
     * Set idTermForCompany
     *
     * @param integer $idTermForCompany
     *
     * @return WalletsLines
     */
    public function setIdTermForCompany($idTermForCompany)
    {
        $this->idTermForCompany = $idTermForCompany;

        return $this;
    }

    /**
     * Get idTermForCompany
     *
     * @return integer
     */
    public function getIdTermForCompany()
    {
        return $this->idTermForCompany;
    }

    /**
     * Set type
     *
     * @param boolean $type
     *
     * @return WalletsLines
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return boolean
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return WalletsLines
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
     * Set display
     *
     * @param boolean $display
     *
     * @return WalletsLines
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return boolean
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return WalletsLines
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return WalletsLines
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
     * @return WalletsLines
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
     * Get idWalletLine
     *
     * @return integer
     */
    public function getIdWalletLine()
    {
        return $this->idWalletLine;
    }
}
