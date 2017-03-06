<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BankLines
 *
 * @ORM\Table(name="bank_lines", indexes={@ORM\Index(name="id_lender_account", columns={"id_lender_account"})})
 * @ORM\Entity
 */
class BankLines
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_wallet_line", type="integer", nullable=false)
     */
    private $idWalletLine;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender_account", type="integer", nullable=false)
     */
    private $idLenderAccount;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_company", type="integer", nullable=false)
     */
    private $idCompany;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_term_for_company", type="integer", nullable=false)
     */
    private $idTermForCompany;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

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
     * @ORM\Column(name="id_bank_line", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBankLine;



    /**
     * Set idWalletLine
     *
     * @param integer $idWalletLine
     *
     * @return BankLines
     */
    public function setIdWalletLine($idWalletLine)
    {
        $this->idWalletLine = $idWalletLine;

        return $this;
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

    /**
     * Set idLenderAccount
     *
     * @param integer $idLenderAccount
     *
     * @return BankLines
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
     * Set idCompany
     *
     * @param integer $idCompany
     *
     * @return BankLines
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
     * Set idTermForCompany
     *
     * @param integer $idTermForCompany
     *
     * @return BankLines
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
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return BankLines
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
     * Set type
     *
     * @param integer $type
     *
     * @return BankLines
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return BankLines
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
     * Set amount
     *
     * @param integer $amount
     *
     * @return BankLines
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
     * @return BankLines
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
     * @return BankLines
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
     * Get idBankLine
     *
     * @return integer
     */
    public function getIdBankLine()
    {
        return $this->idBankLine;
    }
}
