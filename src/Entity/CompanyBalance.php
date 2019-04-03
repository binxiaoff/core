<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyBalance
 *
 * @ORM\Table(name="company_balance", indexes={@ORM\Index(name="idx_company_balance_id_bilan_balance_type", columns={"id_bilan", "id_balance_type"}), @ORM\Index(name="id_balance_type", columns={"id_balance_type"})})
 * @ORM\Entity
 */
class CompanyBalance
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_bilan", type="integer")
     */
    private $idBilan;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float", precision=10, scale=0)
     */
    private $value = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_balance", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBalance;

    /**
     * @var \Unilend\Entity\CompanyBalanceType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\CompanyBalanceType")
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
     * @param \Unilend\Entity\CompanyBalanceType $idBalanceType
     *
     * @return CompanyBalance
     */
    public function setIdBalanceType(\Unilend\Entity\CompanyBalanceType $idBalanceType = null)
    {
        $this->idBalanceType = $idBalanceType;

        return $this;
    }

    /**
     * Get idBalanceType
     *
     * @return \Unilend\Entity\CompanyBalanceType
     */
    public function getIdBalanceType()
    {
        return $this->idBalanceType;
    }
}
