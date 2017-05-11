<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DailyStateBalanceHistory
 *
 * @ORM\Table(name="daily_state_balance_history", indexes={@ORM\Index(name="idx_date", columns={"date"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class DailyStateBalanceHistory
{
    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string", length=14, nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="lender_borrower_balance", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $lenderBorrowerBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="unilend_promotional_balance", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $unilendPromotionalBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="unilend_balance", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $unilendBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_balance", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $taxBalance;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set date
     *
     * @param string $date
     *
     * @return DailyStateBalanceHistory
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set lenderBorrowerBalance
     *
     * @param string $lenderBorrowerBalance
     *
     * @return DailyStateBalanceHistory
     */
    public function setLenderBorrowerBalance($lenderBorrowerBalance)
    {
        $this->lenderBorrowerBalance = $lenderBorrowerBalance;

        return $this;
    }

    /**
     * Get lenderBorrowerBalance
     *
     * @return string
     */
    public function getLenderBorrowerBalance()
    {
        return $this->lenderBorrowerBalance;
    }

    /**
     * Set unilendPromotionalBalance
     *
     * @param string $unilendPromotionalBalance
     *
     * @return DailyStateBalanceHistory
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
     * @return DailyStateBalanceHistory
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
     * @return DailyStateBalanceHistory
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
     * @return DailyStateBalanceHistory
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
