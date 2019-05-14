<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\{Embeddable\LendingRate, Embeddable\Money, Tranche, Wallet};

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
     * @var Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_wallet", nullable=false)
     * })
     */
    protected $wallet;

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
     * @return Wallet
     */
    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    /**
     * @param Wallet $wallet
     *
     * @return self
     */
    public function setWallet(Wallet $wallet): self
    {
        $this->wallet = $wallet;

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
