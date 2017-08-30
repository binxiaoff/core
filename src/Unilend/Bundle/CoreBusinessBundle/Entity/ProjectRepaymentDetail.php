<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRepaymentDetail
 *
 * @ORM\Table(name="project_repayment_detail", indexes={@ORM\Index(name="idx_project_repayment_task_details_id_task", columns={"id_task"}), @ORM\Index(name="idx_project_repayment_task_details_id_wallet", columns={"id_wallet"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ProjectRepaymentDetailRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectRepaymentDetail
{
    const STATUS_PENDING = 0;
    const STATUS_TREATED = 1;

    const CAPITAL_UNCOMPLETED = 0;
    const CAPITAL_COMPLETED   = 1;

    const INTEREST_UNCOMPLETED = 0;
    const INTEREST_COMPLETED   = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="capital", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $capital;

    /**
     * @var string
     *
     * @ORM\Column(name="interest", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $interest;

    /**
     * If the repayment schedule will be completed after the repayment
     *
     * @var boolean
     *
     * @ORM\Column(name="capital_completed", type="boolean", nullable=false)
     */
    private $capitalCompleted;

    /**
     * If the repayment schedule will be completed after the repayment
     *
     * @var boolean
     *
     * @ORM\Column(name="interest_completed", type="boolean", nullable=false)
     */
    private $interestCompleted;

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet", referencedColumnName="id")
     * })
     */
    private $idWallet;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_task", referencedColumnName="id")
     * })
     */
    private $idTask;

    /**
     * Set capital
     *
     * @param string $capital
     *
     * @return ProjectRepaymentDetail
     */
    public function setCapital($capital)
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * Get capital
     *
     * @return string
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * Set interest
     *
     * @param string $interest
     *
     * @return ProjectRepaymentDetail
     */
    public function setInterest($interest)
    {
        $this->interest = $interest;

        return $this;
    }

    /**
     * Get interest
     *
     * @return string
     */
    public function getInterest()
    {
        return $this->interest;
    }

    /**
     * Set capitalCompleted
     *
     * @param boolean $capitalCompleted
     *
     * @return ProjectRepaymentDetail
     */
    public function setCapitalCompleted($capitalCompleted)
    {
        $this->capitalCompleted = $capitalCompleted;

        return $this;
    }

    /**
     * Get capitalCompleted
     *
     * @return boolean
     */
    public function getCapitalCompleted()
    {
        return $this->capitalCompleted;
    }

    /**
     * Set interestCompleted
     *
     * @param boolean $interestCompleted
     *
     * @return ProjectRepaymentDetail
     */
    public function setInterestCompleted($interestCompleted)
    {
        $this->interestCompleted = $interestCompleted;

        return $this;
    }

    /**
     * Get interestCompleted
     *
     * @return boolean
     */
    public function getInterestCompleted()
    {
        return $this->interestCompleted;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return ProjectRepaymentDetail
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectRepaymentDetail
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
     * @return ProjectRepaymentDetail
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idWallet
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet
     *
     * @return ProjectRepaymentDetail
     */
    public function setIdWallet(\Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet = null)
    {
        $this->idWallet = $idWallet;

        return $this;
    }

    /**
     * Get idWallet
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdWallet()
    {
        return $this->idWallet;
    }

    /**
     * Set idTask
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask $idTask
     *
     * @return ProjectRepaymentDetail
     */
    public function setIdTask(\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask $idTask = null)
    {
        $this->idTask = $idTask;

        return $this;
    }

    /**
     * Get idTask
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask
     */
    public function getIdTask()
    {
        return $this->idTask;
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

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
