<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderEvaluation
 *
 * @ORM\Table(name="lender_evaluation", indexes={@ORM\Index(name="id_lender_questionnaire", columns={"id_lender_questionnaire"})})
 * @ORM\Entity
 */
class LenderEvaluation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender", type="integer", nullable=false)
     */
    private $idLender;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiry_date", type="datetime", nullable=false)
     */
    private $expiryDate;

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
     * @ORM\Column(name="id_lender_evaluation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderEvaluation;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\LenderQuestionnaire
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\LenderQuestionnaire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_questionnaire", referencedColumnName="id_lender_questionnaire")
     * })
     */
    private $idLenderQuestionnaire;



    /**
     * Set idLender
     *
     * @param integer $idLender
     *
     * @return LenderEvaluation
     */
    public function setIdLender($idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return integer
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\LenderQuestionnaire $idLenderQuestionnaire
     *
     * @return LenderEvaluation
     */
    public function setIdLenderQuestionnaire(\Unilend\Bundle\CoreBusinessBundle\Entity\LenderQuestionnaire $idLenderQuestionnaire = null)
    {
        $this->idLenderQuestionnaire = $idLenderQuestionnaire;

        return $this;
    }

    /**
     * Get idLenderQuestionnaire
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\LenderQuestionnaire
     */
    public function getIdLenderQuestionnaire()
    {
        return $this->idLenderQuestionnaire;
    }
}
