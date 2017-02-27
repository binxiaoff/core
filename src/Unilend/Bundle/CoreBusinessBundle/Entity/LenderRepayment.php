<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderRepayment
 *
 * @ORM\Table(name="lender_repayment", indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="id_company", columns={"id_company"})})
 * @ORM\Entity
 */
class LenderRepayment
{
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
     * @ORM\Column(name="id_lender_repayment", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderRepayment;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id_lender_account")
     * })
     */
    private $idLender;



    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return LenderRepayment
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
     * @return LenderRepayment
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
     * @return LenderRepayment
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
     * Get idLenderRepayment
     *
     * @return integer
     */
    public function getIdLenderRepayment()
    {
        return $this->idLenderRepayment;
    }

    /**
     * Set idCompany
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Companies $idCompany
     *
     * @return LenderRepayment
     */
    public function setIdCompany(\Unilend\Bundle\CoreBusinessBundle\Entity\Companies $idCompany = null)
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
     * Set idLender
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts $idLender
     *
     * @return LenderRepayment
     */
    public function setIdLender(\Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts $idLender = null)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts
     */
    public function getIdLender()
    {
        return $this->idLender;
    }
}
