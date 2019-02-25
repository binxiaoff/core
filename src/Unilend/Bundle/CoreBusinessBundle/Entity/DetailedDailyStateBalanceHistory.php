<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DetailedDailyStateBalanceHistory
 *
 * @ORM\Table(name="detailed_daily_state_balance_history")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 */
class DetailedDailyStateBalanceHistory
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false, unique=true)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="theoretical_balance", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $theoreticalBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="lender_balance", type="decimal", precision=12, scale=2)
     */
    private $lenderBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="borrower_balance", type="decimal", precision=12, scale=2)
     */
    private $borrowerBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="debt_collector_balance", type="decimal", precision=12, scale=2)
     */
    private $debtCollectorBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="unilend_promotional_balance", type="decimal", precision=12, scale=2)
     */
    private $unilendPromotionalBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="unilend_balance", type="decimal", precision=12, scale=2)
     */
    private $unilendBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_balance", type="decimal", precision=12, scale=2)
     */
    private $taxBalance;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return DetailedDailyStateBalanceHistory
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
     * Set theoreticalBalance
     *
     * @param string $theoreticalBalance
     *
     * @return DetailedDailyStateBalanceHistory
     */
    public function setTheoreticalBalance($theoreticalBalance)
    {
        $this->theoreticalBalance = $theoreticalBalance;

        return $this;
    }

    /**
     * Get theoreticalBalance
     *
     * @return string
     */
    public function getTheoreticalBalance()
    {
        return $this->theoreticalBalance;
    }

    /**
     * Set lenderBalance
     *
     * @param string $lenderBalance
     *
     * @return DetailedDailyStateBalanceHistory
     */
    public function setLenderBalance($lenderBalance)
    {
        $this->lenderBalance = $lenderBalance;

        return $this;
    }

    /**
     * Get lenderBalance
     *
     * @return string
     */
    public function getLenderBalance()
    {
        return $this->lenderBalance;
    }

    /**
     * Set borrowerBalance
     *
     * @param string $borrowerBalance
     *
     * @return DetailedDailyStateBalanceHistory
     */
    public function setBorrowerBalance($borrowerBalance)
    {
        $this->borrowerBalance = $borrowerBalance;

        return $this;
    }

    /**
     * Get borrowerBalance
     *
     * @return string
     */
    public function getBorrowerBalance()
    {
        return $this->borrowerBalance;
    }

    /**
     * Set debtCollectorBalance
     *
     * @param string $debtCollectorBalance
     *
     * @return DetailedDailyStateBalanceHistory
     */
    public function setDebtCollectorBalance($debtCollectorBalance)
    {
        $this->debtCollectorBalance = $debtCollectorBalance;

        return $this;
    }

    /**
     * Get debtCollectorBalance
     *
     * @return string
     */
    public function getDebtCollectorBalance()
    {
        return $this->debtCollectorBalance;
    }

    /**
     * Set unilendPromotionalBalance
     *
     * @param string $unilendPromotionalBalance
     *
     * @return DetailedDailyStateBalanceHistory
     */
    public function setUnilendPromotionalBalance($unilendPromotionalBalance)
    {
        $this->unilendPromotionalBalance = $unilendPromotionalBalance;

        return $this;
    }

    /**
     * Get unilendPromotionalBalance
     *
     * @return string
     */
    public function getUnilendPromotionalBalance()
    {
        return $this->unilendPromotionalBalance;
    }

    /**
     * Set unilendBalance
     *
     * @param string $unilendBalance
     *
     * @return DetailedDailyStateBalanceHistory
     */
    public function setUnilendBalance($unilendBalance)
    {
        $this->unilendBalance = $unilendBalance;

        return $this;
    }

    /**
     * Get unilendBalance
     *
     * @return string
     */
    public function getUnilendBalance()
    {
        return $this->unilendBalance;
    }

    /**
     * Set taxBalance
     *
     * @param string $taxBalance
     *
     * @return DetailedDailyStateBalanceHistory
     */
    public function setTaxBalance($taxBalance)
    {
        $this->taxBalance = $taxBalance;

        return $this;
    }

    /**
     * Get taxBalance
     *
     * @return string
     */
    public function getTaxBalance()
    {
        return $this->taxBalance;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return DetailedDailyStateBalanceHistory
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
}
