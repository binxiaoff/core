<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderEvaluation
 *
 * @ORM\Table(name="lender_evaluation", indexes={@ORM\Index(name="id_lender_questionnaire", columns={"id_lender_questionnaire"}), @ORM\Index(name="fk_lender_evaluation_id_lender", columns={"id_lender"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\LenderEvaluationRepository")
 */
class LenderEvaluation
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiry_date", type="datetime", nullable=true)
     */
    private $expiryDate;

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
     * @var \DateTime|null
     *
     * @ORM\Column(name="validated", type="datetime", nullable=true)
     */
    private $validated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_lender_evaluation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderEvaluation;

    /**
     * @var \Unilend\Entity\LenderQuestionnaire
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\LenderQuestionnaire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_questionnaire", referencedColumnName="id_lender_questionnaire", nullable=false)
     * })
     */
    private $idLenderQuestionnaire;

    /**
     * @var \Unilend\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id", nullable=false)
     * })
     */
    private $idLender;



    /**
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     *
     * @return LenderEvaluation
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LenderEvaluation
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
     * @return LenderEvaluation
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
     * Get idLenderEvaluation
     *
     * @return integer
     */
    public function getIdLenderEvaluation()
    {
        return $this->idLenderEvaluation;
    }

    /**
     * Set idLenderQuestionnaire
     *
     * @param \Unilend\Entity\LenderQuestionnaire $idLenderQuestionnaire
     *
     * @return LenderEvaluation
     */
    public function setIdLenderQuestionnaire(LenderQuestionnaire $idLenderQuestionnaire)
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

    /**
     * Set idLender
     *
     * @param \Unilend\Entity\Wallet $idLender
     *
     * @return LenderEvaluation
     */
    public function setIdLender(Wallet $idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return \Unilend\Entity\Wallet
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidated(): ?\DateTime
    {
        return $this->validated;
    }

    /**
     * @param \DateTime|null $validated
     *
     * @return LenderEvaluation
     */
    public function setValidated(?\DateTime $validated = null): LenderEvaluation
    {
        $this->validated = $validated;

        return $this;
    }
}
