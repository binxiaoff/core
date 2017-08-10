<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRepaymentTask
 *
 * @ORM\Table(name="project_repayment_task", indexes={@ORM\Index(name="idx_project_repayment_task_id_project", columns={"id_project"}), @ORM\Index(name="idx_project_repayment_task_id_project_sequence", columns={"id_project", "sequence"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectRepaymentTask
{
    const TYPE_NORMAL          = 1;
    const TYPE_REGULARIZATION  = 2;
    const TYPE_EARLY           = 3;
    const TYPE_DEBT_COLLECTION = 4;

    const STATUS_PENDING         = 0;
    const STATUS_TO_VALIDATE     = 1;
    const STATUS_READY_FOR_REPAY = 2;
    const STATUS_IN_PROGRESS     = 3;
    const STATUS_ERROR           = -1;
    const STATUS_REPAID          = 4;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="sequence", type="integer", nullable=true)
     */
    private $sequence;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

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
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="repay_at", type="datetime", nullable=true)
     */
    private $repayAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @var ProjectRepaymentTaskLog[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog", mappedBy="idTask")
     */
    private $taskLogs;

    public function __construct()
    {
        $this->taskLogs = new ArrayCollection();
    }

    /**
     * Set amount
     *
     * @param string $amount
     *
     * @return ProjectRepaymentTask
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set sequence
     *
     * @param integer $sequence
     *
     * @return ProjectRepaymentTask
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence
     *
     * @return integer
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return ProjectRepaymentTask
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return ProjectRepaymentTask
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectRepaymentTask
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
     * @return ProjectRepaymentTask
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
     * Set idProject
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject
     *
     * @return ProjectRepaymentTask
     */
    public function setIdProject(\Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Get repayAt
     *
     * @return \DateTime
     */
    public function getRepayAt()
    {
        return $this->repayAt;
    }

    /**
     * Set repayAt
     *
     * @param \DateTime $repayAt
     *
     * @return ProjectRepaymentTask
     */
    public function setRepayAt($repayAt)
    {
        $this->repayAt = $repayAt;

        return $this;
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

    /**
     * @return ProjectRepaymentTaskLog[]
     */
    public function getTaskLogs()
    {
        return $this->taskLogs;
    }

    /**
     * @param ProjectRepaymentTaskLog[] $taskLogs
     *
     * @return ProjectRepaymentTask
     */
    public function setTaskLogs($taskLogs)
    {
        $this->taskLogs = $taskLogs;

        return $this;
    }
}
