<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyBalance
 *
 * @ORM\Table(name="company_balance", indexes={@ORM\Index(name="id_bilan", columns={"id_bilan"}), @ORM\Index(name="id_balance_type", columns={"id_balance_type"})})
 * @ORM\Entity
 */
class CompanyBalance
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_bilan", type="integer", nullable=false)
     */
    private $idBilan;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float", precision=10, scale=0, nullable=false)
     */
    private $value = '0';

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
     * @ORM\Column(name="id_balance", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBalance;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBalanceType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBalanceType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_balance_type", referencedColumnName="id_balance_type")
     * })
     */
    private $idBalanceType;



    /**
     * Set idBilan
     *
     * @param integer $idBilan
     *
     * @return CompanyBalance
     */
    public function setIdBilan($idBilan)
    {
        $this->idBilan = $idBilan;

        return $this;
    }

    /**
     * Get idBilan
     *
     * @return integer
     */
    public function getIdBilan()
    {
        return $this->idBilan;
    }

    /**
     * Set value
     *
     * @param float $value
     *
     * @return CompanyBalance
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return CompanyBalance
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
     * @return CompanyBalance
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
     * Get idBalance
     *
     * @return integer
     */
    public function getIdBalance()
    {
        return $this->idBalance;
    }

    /**
     * Set idBalanceType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBalanceType $idBalanceType
     *
     * @return CompanyBalance
     */
    public function setIdBalanceType(\Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBalanceType $idBalanceType = null)
    {
        $this->idBalanceType = $idBalanceType;

        return $this;
    }

    /**
     * Get idBalanceType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBalanceType
     */
    public function getIdBalanceType()
    {
        return $this->idBalanceType;
    }
}
