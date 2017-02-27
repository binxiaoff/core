<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tax
 *
 * @ORM\Table(name="tax", indexes={@ORM\Index(name="id_tax_type", columns={"id_tax_type"}), @ORM\Index(name="id_transaction", columns={"id_transaction"}), @ORM\Index(name="tax_added_index", columns={"added"})})
 * @ORM\Entity
 */
class Tax
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_transaction", type="integer", nullable=false)
     */
    private $idTransaction;

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
     * @ORM\Column(name="id_tax", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTax;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\TaxType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\TaxType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_tax_type", referencedColumnName="id_tax_type")
     * })
     */
    private $idTaxType;



    /**
     * Set idTransaction
     *
     * @param integer $idTransaction
     *
     * @return Tax
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
     * Set amount
     *
     * @param integer $amount
     *
     * @return Tax
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
     * @return Tax
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
     * @return Tax
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
     * Get idTax
     *
     * @return integer
     */
    public function getIdTax()
    {
        return $this->idTax;
    }

    /**
     * Set idTaxType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\TaxType $idTaxType
     *
     * @return Tax
     */
    public function setIdTaxType(\Unilend\Bundle\CoreBusinessBundle\Entity\TaxType $idTaxType = null)
    {
        $this->idTaxType = $idTaxType;

        return $this;
    }

    /**
     * Get idTaxType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\TaxType
     */
    public function getIdTaxType()
    {
        return $this->idTaxType;
    }
}
