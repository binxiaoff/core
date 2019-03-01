<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\InterestRateIndexType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

trait Lendable
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $amount;

    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    protected $project;

    /**
     * @var Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet", referencedColumnName="id", nullable=false)
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
     * In case of fixed interest rate, the type will be null, thus the indexed rate is considered as zero.
     *
     * @var InterestRateIndexType|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\InterestRateIndexType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_interest_rate_index_type", referencedColumnName="id")
     * })
     */
    protected $interestRateIndexType;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var float
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     */
    protected $rate;

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     *
     * @return self
     */
    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return Projects
     */
    public function getProject(): Projects
    {
        return $this->project;
    }

    /**
     * @param Projects $project
     *
     * @return self
     */
    public function setProject(Projects $project): self
    {
        $this->project = $project;

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
     * @return InterestRateIndexType|null
     */
    public function getInterestRateIndexType(): ?InterestRateIndexType
    {
        return $this->interestRateIndexType;
    }

    /**
     * @param InterestRateIndexType|null $interestRateIndexType
     *
     * @return self
     */
    public function setInterestRateIndexType(?InterestRateIndexType $interestRateIndexType): self
    {
        $this->interestRateIndexType = $interestRateIndexType;

        return $this;
    }

    /**
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * @param float $rate
     *
     * @return self
     */
    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }
}
