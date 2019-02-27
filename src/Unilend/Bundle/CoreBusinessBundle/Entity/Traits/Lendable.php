<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\InterestRateIndexType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

trait Lendable
{
    use Timestampable;

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
     *   @ORM\JoinColumn(name="rate_index_type", referencedColumnName="id")
     * })
     */
    protected $rateIndexType;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var float
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     */
    protected $rate;

    /**
     * The recurring management fee for each repayment schedule (Frais de gestion running) in percentage of the amount in the context
     *
     * @var float
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     */
    protected $preciput;

    /**
     * The one-shoot administration fee (Frais de dossier) in percentage of the amount in the context
     *
     * @var float
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     */
    protected $administrationFee;

    /**
     * The one-shoot setup fee (Commission de mise en place) in percentage of the project amount
     *
     * @var float
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     */
    protected $setUpFee;

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
    public function getRateIndexType(): ?InterestRateIndexType
    {
        return $this->rateIndexType;
    }

    /**
     * @param InterestRateIndexType|null $rateIndexType
     *
     * @return self
     */
    public function setRateIndexType(?InterestRateIndexType $rateIndexType): self
    {
        $this->rateIndexType = $rateIndexType;

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

    /**
     * @return float
     */
    public function getPreciput(): float
    {
        return $this->preciput;
    }

    /**
     * @param float $preciput
     *
     * @return self
     */
    public function setPreciput(float $preciput): self
    {
        $this->preciput = $preciput;

        return $this;
    }

    /**
     * @return float
     */
    public function getAdministrationFee(): float
    {
        return $this->administrationFee;
    }

    /**
     * @param float $administrationFee
     *
     * @return self
     */
    public function setAdministrationFee(float $administrationFee): self
    {
        $this->administrationFee = $administrationFee;

        return $this;
    }

    /**
     * @return float
     */
    public function getSetUpFee(): float
    {
        return $this->setUpFee;
    }

    /**
     * @param float $setUpFee
     *
     * @return self
     */
    public function setSetUpFee(float $setUpFee): self
    {
        $this->setUpFee = $setUpFee;

        return $this;
    }
}
