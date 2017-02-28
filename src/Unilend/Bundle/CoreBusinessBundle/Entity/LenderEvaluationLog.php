<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderEvaluationLog
 *
 * @ORM\Table(name="lender_evaluation_log", indexes={@ORM\Index(name="id_lender_evaluation", columns={"id_lender_evaluation"})})
 * @ORM\Entity
 */
class LenderEvaluationLog
{
    /**
     * @var string
     *
     * @ORM\Column(name="event", type="string", length=191, nullable=false)
     */
    private $event;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", length=65535, nullable=false)
     */
    private $message;

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
     * @var integer
     *
     * @ORM\Column(name="id_lender_evaluation_log", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderEvaluationLog;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\LenderEvaluation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\LenderEvaluation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_evaluation", referencedColumnName="id_lender_evaluation")
     * })
     */
    private $idLenderEvaluation;



    /**
     * Set event
     *
     * @param string $event
     *
     * @return LenderEvaluationLog
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return LenderEvaluationLog
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LenderEvaluationLog
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
     * @return LenderEvaluationLog
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
     * Get idLenderEvaluationLog
     *
     * @return integer
     */
    public function getIdLenderEvaluationLog()
    {
        return $this->idLenderEvaluationLog;
    }

    /**
     * Set idLenderEvaluation
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\LenderEvaluation $idLenderEvaluation
     *
     * @return LenderEvaluationLog
     */
    public function setIdLenderEvaluation(\Unilend\Bundle\CoreBusinessBundle\Entity\LenderEvaluation $idLenderEvaluation = null)
    {
        $this->idLenderEvaluation = $idLenderEvaluation;

        return $this;
    }

    /**
     * Get idLenderEvaluation
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\LenderEvaluation
     */
    public function getIdLenderEvaluation()
    {
        return $this->idLenderEvaluation;
    }
}
