<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRepaymentTaskLog
 *
 * @ORM\Table(name="project_repayment_task_log", indexes={@ORM\Index(name="idx_project_repayment_task_log_id_task", columns={"id_task"})})
 * @ORM\Entity
 */
class ProjectRepaymentTaskLog
{
    /**
     * @var string
     *
     * @ORM\Column(name="repaid_amount", type="decimal", precision=12, scale=2)
     */
    private $repaidAmount;

    /**
     * @var int
     *
     * @ORM\Column(name="repayment_nb", type="integer")
     */
    private $repaymentNb;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started", type="datetime")
     */
    private $started;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ended", type="datetime", nullable=true)
     */
    private $ended;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var ProjectRepaymentTask
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectRepaymentTask", inversedBy="taskLogs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_task", referencedColumnName="id", nullable=false)
     * })
     */
    private $idTask;

    /**
     * Set repaidAmount
     *
     * @param string $repaidAmount
     *
     * @return ProjectRepaymentTaskLog
     */
    public function setRepaidAmount($repaidAmount)
    {
        $this->repaidAmount = $repaidAmount;

        return $this;
    }

    /**
     * Get repaidAmount
     *
     * @return string
     */
    public function getRepaidAmount()
    {
        return $this->repaidAmount;
    }

    /**
     * Set repaymentNb
     *
     * @param integer $repaymentNb
     *
     * @return ProjectRepaymentTaskLog
     */
    public function setRepaymentNb($repaymentNb)
    {
        $this->repaymentNb = $repaymentNb;

        return $this;
    }

    /**
     * Get repaymentNb
     *
     * @return integer
     */
    public function getRepaymentNb()
    {
        return $this->repaymentNb;
    }

    /**
     * Set started
     *
     * @param \DateTime $started
     *
     * @return ProjectRepaymentTaskLog
     */
    public function setStarted($started)
    {
        $this->started = $started;

        return $this;
    }

    /**
     * Get started
     *
     * @return \DateTime
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Set ended
     *
     * @param \DateTime $ended
     *
     * @return ProjectRepaymentTaskLog
     */
    public function setEnded($ended)
    {
        $this->ended = $ended;

        return $this;
    }

    /**
     * Get ended
     *
     * @return \DateTime
     */
    public function getEnded()
    {
        return $this->ended;
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
     * Set idTask
     *
     * @param ProjectRepaymentTask $idTask
     *
     * @return ProjectRepaymentTaskLog
     */
    public function setIdTask(ProjectRepaymentTask $idTask = null)
    {
        $this->idTask = $idTask;

        return $this;
    }

    /**
     * Get idTask
     *
     * @return ProjectRepaymentTask
     */
    public function getIdTask()
    {
        return $this->idTask;
    }
}
