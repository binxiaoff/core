<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\{Embeddable\LendingRate, Project, Wallet};

trait LendableTrait
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     */
    protected $amount;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Project")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    protected $project;

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
     * Initialize the trait.
     */
    public function traitInit(): void
    {
        $this->rate = new LendingRate();
    }

    /**
     * @return int|null
     */
    public function getAmount(): ?int
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
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     *
     * @return self
     */
    public function setProject(Project $project): self
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
