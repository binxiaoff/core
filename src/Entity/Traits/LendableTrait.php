<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Embeddable\{LendingRate, Money};
use Unilend\Entity\{Companies, Tranche};

trait LendableTrait
{
    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tranche")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false)
     * })
     */
    protected $tranche;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_lender", referencedColumnName="id_company", nullable=false)
     * })
     */
    protected $lender;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    protected $status;

    /**
     * @var LendingRate
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\LendingRate")
     */
    protected $rate;

    /**
     * @var Money
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Money")
     */
    private $money;

    /**
     * Initialize the trait.
     */
    public function traitInit(): void
    {
        $this->rate  = new LendingRate();
        $this->money = new Money();
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }

    /**
     * @param Money $money
     *
     * @return self
     */
    public function setMoney(Money $money): self
    {
        $this->money = $money;

        return $this;
    }

    /**
     * @return Tranche
     */
    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    /**
     * @param Tranche $tranche
     *
     * @return self
     */
    public function setTranche(Tranche $tranche): self
    {
        $this->tranche = $tranche;

        return $this;
    }

    /**
     * @return Companies
     */
    public function getLender(): Companies
    {
        return $this->lender;
    }

    /**
     * @param Companies $lender
     *
     * @return self
     */
    public function setLender(Companies $lender): self
    {
        $this->lender = $lender;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return LendingRate
     */
    public function getRate(): LendingRate
    {
        return $this->rate;
    }

    /**
     * @param LendingRate $rate
     *
     * @return self
     */
    public function setRate(LendingRate $rate): self
    {
        $this->rate = $rate;

        return $this;
    }
}
