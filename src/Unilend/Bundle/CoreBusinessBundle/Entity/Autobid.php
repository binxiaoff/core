<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Autobid
 *
 * @ORM\Table(name="autobid", indexes={@ORM\Index(name="idx_autobid_eval_period", columns={"evaluation", "id_period", "status"}), @ORM\Index(name="idx_autobid_id_lender", columns={"id_lender"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\AutobidRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Autobid
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE   = 1;
    const STATUS_ARCHIVED = 2;

    const REGULAR_SETTINGS_COUNT = 30;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="evaluation", type="string", length=2, nullable=false)
     */
    private $evaluation;

    /**
     * @var ProjectPeriod
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectPeriod")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_period", referencedColumnName="id_period")
     * })
     */
    private $idPeriod;

    /**
     * @var string
     *
     * @ORM\Column(name="rate_min", type="decimal", precision=3, scale=1, nullable=false)
     */
    private $rateMin;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

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
     * @ORM\Column(name="id_autobid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAutobid;

    /**
     * @var Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id")
     * })
     */
    private $idLender;

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Autobid
     */
    public function setStatus(int $status): Autobid
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set evaluation
     *
     * @param string $evaluation
     *
     * @return Autobid
     */
    public function setEvaluation(string $evaluation): Autobid
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    /**
     * Get evaluation
     *
     * @return string
     */
    public function getEvaluation(): string
    {
        return $this->evaluation;
    }

    /**
     * Set idPeriod
     *
     * @param ProjectPeriod $idPeriod
     *
     * @return Autobid
     */
    public function setIdPeriod(ProjectPeriod $idPeriod): Autobid
    {
        $this->idPeriod = $idPeriod;

        return $this;
    }

    /**
     * Get idPeriod
     *
     * @return ProjectPeriod
     */
    public function getIdPeriod(): ProjectPeriod
    {
        return $this->idPeriod;
    }

    /**
     * Set rateMin
     *
     * @param string $rateMin
     *
     * @return Autobid
     */
    public function setRateMin(string $rateMin): Autobid
    {
        $this->rateMin = $rateMin;

        return $this;
    }

    /**
     * Get rateMin
     *
     * @return string
     */
    public function getRateMin(): string
    {
        return $this->rateMin;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return Autobid
     */
    public function setAmount(int $amount): Autobid
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Autobid
     */
    public function setAdded(\DateTime $added): Autobid
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
     * @param \DateTime $updated
     *
     * @return Autobid
     */
    public function setUpdated(\DateTime $updated): Autobid
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
     * Get idAutobid
     *
     * @return integer
     */
    public function getIdAutobid(): int
    {
        return $this->idAutobid;
    }

    /**
     * Set idLender
     *
     * @param Wallet $idLender
     *
     * @return Autobid
     */
    public function setIdLender(Wallet $idLender): Autobid
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return Wallet
     */
    public function getIdLender(): Wallet
    {
        return $this->idLender;
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
