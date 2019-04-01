<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderEvaluationAnswer
 *
 * @ORM\Table(name="lender_evaluation_answer", indexes={@ORM\Index(name="id_lender_questionnaire_question", columns={"id_lender_questionnaire_question"}), @ORM\Index(name="id_lender_evaluation", columns={"id_lender_evaluation"})})
 * @ORM\Entity
 */
class LenderEvaluationAnswer
{
    /**
     * @var string
     *
     * @ORM\Column(name="first_answer", type="string", length=191)
     */
    private $firstAnswer;

    /**
     * @var string
     *
     * @ORM\Column(name="second_answer", type="string", length=191)
     */
    private $secondAnswer;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_lender_evaluation_answer", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderEvaluationAnswer;

    /**
     * @var \Unilend\Entity\LenderEvaluation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\LenderEvaluation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_evaluation", referencedColumnName="id_lender_evaluation", nullable=false)
     * })
     */
    private $idLenderEvaluation;

    /**
     * @var \Unilend\Entity\LenderQuestionnaireQuestion
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\LenderQuestionnaireQuestion")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_questionnaire_question", referencedColumnName="id_lender_questionnaire_question", nullable=false)
     * })
     */
    private $idLenderQuestionnaireQuestion;



    /**
     * Set firstAnswer
     *
     * @param string $firstAnswer
     *
     * @return LenderEvaluationAnswer
     */
    public function setFirstAnswer($firstAnswer)
    {
        $this->firstAnswer = $firstAnswer;

        return $this;
    }

    /**
     * Get firstAnswer
     *
     * @return string
     */
    public function getFirstAnswer()
    {
        return $this->firstAnswer;
    }

    /**
     * Set secondAnswer
     *
     * @param string $secondAnswer
     *
     * @return LenderEvaluationAnswer
     */
    public function setSecondAnswer($secondAnswer)
    {
        $this->secondAnswer = $secondAnswer;

        return $this;
    }

    /**
     * Get secondAnswer
     *
     * @return string
     */
    public function getSecondAnswer()
    {
        return $this->secondAnswer;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return LenderEvaluationAnswer
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
     * @return LenderEvaluationAnswer
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
     * @return LenderEvaluationAnswer
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
     * Get idLenderEvaluationAnswer
     *
     * @return integer
     */
    public function getIdLenderEvaluationAnswer()
    {
        return $this->idLenderEvaluationAnswer;
    }

    /**
     * Set idLenderEvaluation
     *
     * @param \Unilend\Entity\LenderEvaluation $idLenderEvaluation
     *
     * @return LenderEvaluationAnswer
     */
    public function setIdLenderEvaluation(\Unilend\Entity\LenderEvaluation $idLenderEvaluation = null)
    {
        $this->idLenderEvaluation = $idLenderEvaluation;

        return $this;
    }

    /**
     * Get idLenderEvaluation
     *
     * @return \Unilend\Entity\LenderEvaluation
     */
    public function getIdLenderEvaluation()
    {
        return $this->idLenderEvaluation;
    }

    /**
     * Set idLenderQuestionnaireQuestion
     *
     * @param \Unilend\Entity\LenderQuestionnaireQuestion $idLenderQuestionnaireQuestion
     *
     * @return LenderEvaluationAnswer
     */
    public function setIdLenderQuestionnaireQuestion(\Unilend\Entity\LenderQuestionnaireQuestion $idLenderQuestionnaireQuestion = null)
    {
        $this->idLenderQuestionnaireQuestion = $idLenderQuestionnaireQuestion;

        return $this;
    }

    /**
     * Get idLenderQuestionnaireQuestion
     *
     * @return \Unilend\Entity\LenderQuestionnaireQuestion
     */
    public function getIdLenderQuestionnaireQuestion()
    {
        return $this->idLenderQuestionnaireQuestion;
    }
}
