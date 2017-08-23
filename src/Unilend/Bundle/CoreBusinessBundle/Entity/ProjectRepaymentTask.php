<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRepaymentTask
 *
 * @ORM\Table(name="project_repayment_task", indexes={@ORM\Index(name="idx_project_repayment_task_id_project", columns={"id_project"}), @ORM\Index(name="idx_project_repayment_task_id_project_sequence", columns={"id_project", "sequence"}), @ORM\Index(name="idx_project_repayment_task_id_wire_transfer_in", columns={"id_wire_transfer_in"}), @ORM\Index(name="idx_project_repayment_task_id_user_suspending", columns={"id_user_suspending"}), @ORM\Index(name="idx_project_repayment_task_id_user_validation", columns={"id_user_validation"}), @ORM\Index(name="idx_project_repayment_task_id_user_creation", columns={"id_user_creation"}), @ORM\Index(name="idx_project_repayment_task_id_user_cancellation", columns={"id_user_cancellation"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ProjectRepaymentTaskRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProjectRepaymentTask
{
    const TYPE_REGULAR           = 1;
    const TYPE_LATE              = 2;
    const TYPE_EARLY             = 3;
    const TYPE_CLOSE_OUT_NETTING = 4;

    const STATUS_ERROR       = -1;
    const STATUS_PENDING     = 1;
    const STATUS_READY       = 2;
    const STATUS_IN_PROGRESS = 3;
    const STATUS_REPAID      = 4;
    const STATUS_CANCELLED   = 5;

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
     * @ORM\Column(name="repay_at", type="date", nullable=false)
     */
    private $repayAt;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_validation", referencedColumnName="id_user")
     * })
     */
    private $idUserValidation;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Receptions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_in", referencedColumnName="id_reception")
     * })
     */
    private $idWireTransferIn;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_suspending", referencedColumnName="id_user")
     * })
     */
    private $idUserSuspending;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_creation", referencedColumnName="id_user")
     * })
     */
    private $idUserCreation;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_cancellation", referencedColumnName="id_user")
     * })
     */
    private $idUserCancellation;

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
     * Get repayAt
     *
     * @return \DateTime
     */
    public function getRepayAt()
    {
        return $this->repayAt;
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
     * Set idUserValidation
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserValidation
     *
     * @return ProjectRepaymentTask
     */
    public function setIdUserValidation(\Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserValidation = null)
    {
        $this->idUserValidation = $idUserValidation;

        return $this;
    }

    /**
     * Get idUserValidation
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     */
    public function getIdUserValidation()
    {
        return $this->idUserValidation;
    }

    /**
     * Set idWireTransferIn
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $idWireTransferIn
     *
     * @return ProjectRepaymentTask
     */
    public function setIdWireTransferIn(\Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $idWireTransferIn = null)
    {
        $this->idWireTransferIn = $idWireTransferIn;

        return $this;
    }

    /**
     * Get idWireTransferIn
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions
     */
    public function getIdWireTransferIn()
    {
        return $this->idWireTransferIn;
    }

    /**
     * Set idUserSuspending
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserSuspending
     *
     * @return ProjectRepaymentTask
     */
    public function setIdUserSuspending(\Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserSuspending = null)
    {
        $this->idUserSuspending = $idUserSuspending;

        return $this;
    }

    /**
     * Get idUserSuspending
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     */
    public function getIdUserSuspending()
    {
        return $this->idUserSuspending;
    }

    /**
     * Set idUserCreation
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserCreation
     *
     * @return ProjectRepaymentTask
     */
    public function setIdUserCreation(\Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserCreation = null)
    {
        $this->idUserCreation = $idUserCreation;

        return $this;
    }

    /**
     * Get idUserCreation
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     */
    public function getIdUserCreation()
    {
        return $this->idUserCreation;
    }

    /**
     * Set idUserCancellation
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserCancellation
     *
     * @return ProjectRepaymentTask
     */
    public function setIdUserCancellation(\Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserCancellation = null)
    {
        $this->idUserCancellation = $idUserCancellation;

        return $this;
    }

    /**
     * Get idUserCancellation
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     */
    public function getIdUserCancellation()
    {
        return $this->idUserCancellation;
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
}
