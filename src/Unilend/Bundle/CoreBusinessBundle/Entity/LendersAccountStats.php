<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LendersAccountStats
 *
 * @ORM\Table(name="lenders_account_stats", indexes={@ORM\Index(name="id_lender_account", columns={"id_lender_account"})})
 * @ORM\Entity
 */
class LendersAccountStats
{
    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=50, nullable=false)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="type_stat", type="string", length=100, nullable=false)
     */
    private $typeStat;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    private $date;

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
     * @ORM\Column(name="id_lenders_accounts_stats", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLendersAccountsStats;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_account", referencedColumnName="id_lender_account")
     * })
     */
    private $idLenderAccount;



    /**
     * Set value
     *
     * @param string $value
     *
     * @return LendersAccountStats
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set typeStat
     *
     * @param string $typeStat
     *
     * @return LendersAccountStats
     */
    public function setTypeStat($typeStat)
    {
        $this->typeStat = $typeStat;

        return $this;
    }

    /**
     * Get typeStat
     *
     * @return string
     */
    public function getTypeStat()
    {
        return $this->typeStat;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return LendersAccountStats
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return LendersAccountStats
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
     * @return LendersAccountStats
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
     * @return LendersAccountStats
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
     * Get idLendersAccountsStats
     *
     * @return integer
     */
    public function getIdLendersAccountsStats()
    {
        return $this->idLendersAccountsStats;
    }

    /**
     * Set idLenderAccount
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts $idLenderAccount
     *
     * @return LendersAccountStats
     */
    public function setIdLenderAccount(\Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts $idLenderAccount = null)
    {
        $this->idLenderAccount = $idLenderAccount;

        return $this;
    }

    /**
     * Get idLenderAccount
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts
     */
    public function getIdLenderAccount()
    {
        return $this->idLenderAccount;
    }
}
