<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRepaymentTask
 *
 * @ORM\Table(name="project_repayment_task", indexes={
 *     @ORM\Index(name="idx_project_repayment_task_id_project", columns={"id_project"}),
 *     @ORM\Index(name="idx_project_repayment_task_id_project_sequence", columns={"id_project", "sequence"}),
 *     @ORM\Index(name="idx_project_repayment_task_id_wire_transfer_in", columns={"id_wire_transfer_in"}),
 *     @ORM\Index(name="idx_project_repayment_task_id_user_suspending", columns={"id_user_suspending"}),
 *     @ORM\Index(name="idx_project_repayment_task_id_user_validation", columns={"id_user_validation"}),
 *     @ORM\Index(name="idx_project_repayment_task_id_user_creation", columns={"id_user_creation"}),
 *     @ORM\Index(name="idx_project_repayment_task_id_user_cancellation", columns={"id_user_cancellation"}),
 *     @ORM\Index(name="idx_project_repayment_task_id_debt_collection_mission", columns={"id_debt_collection_mission"})
 * })
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

    const STATUS_PLANNED = [
        self::STATUS_ERROR,
        self::STATUS_PENDING,
        self::STATUS_READY,
        self::STATUS_IN_PROGRESS
    ];

    /**
     * @var string
     *
     * @ORM\Column(name="capital", type="decimal", precision=12, scale=2)
     */
    private $capital;

    /**
     * @var string
     *
     * @ORM\Column(name="interest", type="decimal", precision=12, scale=2)
     */
    private $interest;

    /**
     * @var string
     *
     * @ORM\Column(name="commission_unilend", type="decimal", precision=10, scale=2)
     */
    private $commissionUnilend;

    /**
     * @var int
     *
     * @ORM\Column(name="sequence", type="integer", nullable=true)
     */
    private $sequence;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="repay_at", type="date")
     */
    private $repayAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_validation", referencedColumnName="id_user")
     * })
     */
    private $idUserValidation;

    /**
     * @var \Unilend\Entity\Receptions
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Receptions", inversedBy="projectRepaymentTasks")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_in", referencedColumnName="id_reception")
     * })
     */
    private $idWireTransferIn;

    /**
     * @var \Unilend\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_suspending", referencedColumnName="id_user")
     * })
     */
    private $idUserSuspending;

    /**
     * @var \Unilend\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_creation", referencedColumnName="id_user")
     * })
     */
    private $idUserCreation;

    /**
     * @var \Unilend\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_cancellation", referencedColumnName="id_user")
     * })
     */
    private $idUserCancellation;

    /**
     * @var \Unilend\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var ProjectRepaymentTaskLog[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProjectRepaymentTaskLog", mappedBy="idTask")
     */
    private $taskLogs;

    /**
     * @var DebtCollectionMission
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\DebtCollectionMission")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_debt_collection_mission", referencedColumnName="id")
     * })
     */
    private $idDebtCollectionMission;

    public function __construct()
    {
        $this->taskLogs = new ArrayCollection();
    }

    /**
     * Set capital
     *
     * @param string $capital
     *
     * @return ProjectRepaymentTask
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
     * @return ProjectRepaymentTask
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
     * Set commissionUnilend
     *
     * @param string $commissionUnilend
     *
     * @return ProjectRepaymentTask
     */
    public function setCommissionUnilend($commissionUnilend)
    {
        $this->commissionUnilend = $commissionUnilend;

        return $this;
    }

    /**
     * Get commissionUnilend
     *
     * @return string
     */
    public function getCommissionUnilend()
    {
        return $this->commissionUnilend;
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
     * @param \Unilend\Entity\Users $idUserValidation
     *
     * @return ProjectRepaymentTask
     */
    public function setIdUserValidation(\Unilend\Entity\Users $idUserValidation = null)
    {
        $this->idUserValidation = $idUserValidation;

        return $this;
    }

    /**
     * Get idUserValidation
     *
     * @return \Unilend\Entity\Users
     */
    public function getIdUserValidation()
    {
        return $this->idUserValidation;
    }

    /**
     * Set idWireTransferIn
     *
     * @param \Unilend\Entity\Receptions $idWireTransferIn
     *
     * @return ProjectRepaymentTask
     */
    public function setIdWireTransferIn(\Unilend\Entity\Receptions $idWireTransferIn = null)
    {
        $this->idWireTransferIn = $idWireTransferIn;

        return $this;
    }

    /**
     * Get idWireTransferIn
     *
     * @return \Unilend\Entity\Receptions
     */
    public function getIdWireTransferIn()
    {
        return $this->idWireTransferIn;
    }

    /**
     * Set idUserSuspending
     *
     * @param \Unilend\Entity\Users $idUserSuspending
     *
     * @return ProjectRepaymentTask
     */
    public function setIdUserSuspending(\Unilend\Entity\Users $idUserSuspending = null)
    {
        $this->idUserSuspending = $idUserSuspending;

        return $this;
    }

    /**
     * Get idUserSuspending
     *
     * @return \Unilend\Entity\Users
     */
    public function getIdUserSuspending()
    {
        return $this->idUserSuspending;
    }

    /**
     * Set idUserCreation
     *
     * @param \Unilend\Entity\Users $idUserCreation
     *
     * @return ProjectRepaymentTask
     */
    public function setIdUserCreation(\Unilend\Entity\Users $idUserCreation = null)
    {
        $this->idUserCreation = $idUserCreation;

        return $this;
    }

    /**
     * Get idUserCreation
     *
     * @return \Unilend\Entity\Users
     */
    public function getIdUserCreation()
    {
        return $this->idUserCreation;
    }

    /**
     * Set idUserCancellation
     *
     * @param \Unilend\Entity\Users $idUserCancellation
     *
     * @return ProjectRepaymentTask
     */
    public function setIdUserCancellation(\Unilend\Entity\Users $idUserCancellation = null)
    {
        $this->idUserCancellation = $idUserCancellation;

        return $this;
    }

    /**
     * Get idUserCancellation
     *
     * @return \Unilend\Entity\Users
     */
    public function getIdUserCancellation()
    {
        return $this->idUserCancellation;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Entity\Projects $idProject
     *
     * @return ProjectRepaymentTask
     */
    public function setIdProject(\Unilend\Entity\Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Entity\Projects
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

    /**
     * Set idDebtCollectionMission
     *
     * @param DebtCollectionMission $idDebtCollectionMission
     *
     * @return ProjectRepaymentTask
     */
    public function setIdDebtCollectionMission(DebtCollectionMission $idDebtCollectionMission = null)
    {
        $this->idDebtCollectionMission = $idDebtCollectionMission;

        return $this;
    }

    /**
     * Get idDebtCollectionMission
     *
     * @return DebtCollectionMission
     */
    public function getIdDebtCollectionMission()
    {
        return $this->idDebtCollectionMission;
    }
}
