<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRepaymentTaskLog
 *
 * @ORM\Table(name="project_repayment_task_log", indexes={@ORM\Index(name="idx_project_repayment_task_log_id_task", columns={"id_task"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectRepaymentTaskLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="sequence", type="smallint", nullable=true)
     */
    private $sequence;

    /**
     * @var string
     *
     * @ORM\Column(name="repaid_amount", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $repaidAmount;

    /**
     * @var integer
     *
     * @ORM\Column(name="repayment_nb", type="integer", nullable=false)
     */
    private $repaymentNb;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started", type="datetime", nullable=false)
     */
    private $started;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ended", type="datetime", nullable=true)
     */
    private $ended;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask", inversedBy="taskLogs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_task", referencedColumnName="id")
     * })
     */
    private $idTask;



    /**
     * Set sequence
     *
     * @param integer $sequence
     *
     * @return ProjectRepaymentTaskLog
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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask $idTask
     *
     * @return ProjectRepaymentTaskLog
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
    public function setStartedValue()
    {
        if (! $this->started instanceof \DateTime || 1 > $this->getStarted()->getTimestamp()) {
            $this->started = new \DateTime();
        }
    }
}
