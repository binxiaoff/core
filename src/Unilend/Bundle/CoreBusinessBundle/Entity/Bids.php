<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Bids
 *
 * @ORM\Table(name="bids", indexes={@ORM\Index(name="id_lender_account", columns={"id_lender_account"}), @ORM\Index(name="idprojectstatus", columns={"id_project", "status"}), @ORM\Index(name="idx_id_autobid", columns={"id_autobid"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\BidsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Bids
{
    const STATUS_PENDING                      = 0;
    const STATUS_ACCEPTED                     = 1;
    const STATUS_REJECTED                     = 2;
    const STATUS_TEMPORARILY_REJECTED_AUTOBID = 3;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Autobid
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Autobid")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_autobid", referencedColumnName="id_autobid")
     * })
     */
    private $idAutobid;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", precision=10, scale=0, nullable=false)
     */
    private $rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=true)
     */
    private $ordre;

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
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_bid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBid;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_account", referencedColumnName="id", nullable=false)
     * })
     */
    private $idLenderAccount;

    /**
     * Set Project
     *
     * @param Projects|null $project
     *
     * @return Bids
     */
    public function setProject(?Projects $project): Bids
    {
        $this->idProject = $project;

        return $this;
    }

    /**
     * Get Project
     *
     * @return Projects|null
     */
    public function getProject(): ?Projects
    {
        return $this->idProject;
    }

    /**
     * Set Autobid
     *
     * @param Autobid|null $autobid
     *
     * @return Bids
     */
    public function setAutobid(?Autobid $autobid): Bids
    {
        $this->idAutobid = $autobid;

        return $this;
    }

    /**
     * Get Autobid
     *
     * @return Autobid|null
     */
    public function getAutobid(): ?Autobid
    {
        return $this->idAutobid;
    }

    /**
     * Set idLenderAccount
     *
     * @param Wallet $idLenderAccount
     *
     * @return Bids
     */
    public function setIdLenderAccount(Wallet $idLenderAccount): Bids
    {
        $this->idLenderAccount = $idLenderAccount;

        return $this;
    }

    /**
     * Get idLenderAccount
     *
     * @return Wallet
     */
    public function getIdLenderAccount(): Wallet
    {
        return $this->idLenderAccount;
    }

    /**
     * Set amount
     *
     * @param int $amount
     *
     * @return Bids
     */
    public function setAmount(int $amount): Bids
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Set rate
     *
     * @param float $rate
     *
     * @return Bids
     */
    public function setRate(float $rate): Bids
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * Set ordre
     *
     * @param int|null $ordre
     *
     * @return Bids
     */
    public function setOrdre(?int $ordre): Bids
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return int|null
     */
    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return Bids
     */
    public function setStatus(int $status): Bids
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Bids
     */
    public function setAdded(\DateTime $added): Bids
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime|null $updated
     *
     * @return Bids
     */
    public function setUpdated(?\DateTime $updated): Bids
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Get idBid
     *
     * @return int
     */
    public function getIdBid(): int
    {
        return $this->idBid;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
