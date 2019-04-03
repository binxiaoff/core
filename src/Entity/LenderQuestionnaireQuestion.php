<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderQuestionnaireQuestion
 *
 * @ORM\Table(name="lender_questionnaire_question", indexes={@ORM\Index(name="idx_order", columns={"order"}), @ORM\Index(name="id_lender_questionnaire", columns={"id_lender_questionnaire"})})
 * @ORM\Entity
 */
class LenderQuestionnaireQuestion
{
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=191)
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="order", type="integer")
     */
    private $order;

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
     * @ORM\Column(name="id_lender_questionnaire_question", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderQuestionnaireQuestion;

    /**
     * @var \Unilend\Entity\LenderQuestionnaire
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\LenderQuestionnaire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_questionnaire", referencedColumnName="id_lender_questionnaire")
     * })
     */
    private $idLenderQuestionnaire;



    /**
     * Set type
     *
     * @param string $type
     *
     * @return LenderQuestionnaireQuestion
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set order
     *
     * @param integer $order
     *
     * @return LenderQuestionnaireQuestion
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LenderQuestionnaireQuestion
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
     * @return LenderQuestionnaireQuestion
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
     * Get idLenderQuestionnaireQuestion
     *
     * @return integer
     */
    public function getIdLenderQuestionnaireQuestion()
    {
        return $this->idLenderQuestionnaireQuestion;
    }

    /**
     * Set idLenderQuestionnaire
     *
     * @param \Unilend\Entity\LenderQuestionnaire $idLenderQuestionnaire
     *
     * @return LenderQuestionnaireQuestion
     */
    public function setIdLenderQuestionnaire(\Unilend\Entity\LenderQuestionnaire $idLenderQuestionnaire = null)
    {
        $this->idLenderQuestionnaire = $idLenderQuestionnaire;

        return $this;
    }

    /**
     * Get idLenderQuestionnaire
     *
     * @return \Unilend\Entity\LenderQuestionnaire
     */
    public function getIdLenderQuestionnaire()
    {
        return $this->idLenderQuestionnaire;
    }
}
