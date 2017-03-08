<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GreenpointAttachment
 *
 * @ORM\Table(name="greenpoint_attachment", uniqueConstraints={@ORM\UniqueConstraint(name="id_attachment", columns={"id_attachment"})}, indexes={@ORM\Index(name="index_gp_id_attachment", columns={"id_attachment"}), @ORM\Index(name="index_gp_id_client", columns={"id_client"})})
 * @ORM\Entity
 */
class GreenpointAttachment
{
    const REVALIDATE_YES = 1;
    const REVALIDATE_NO  = 0;

    const FINAL_STATUS_YES = 1;
    const FINAL_STATUS_NO  = 0;
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="validation_status", type="integer", nullable=false)
     */
    private $validationStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="validation_code", type="string", length=2, nullable=false)
     */
    private $validationCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="control_level", type="integer", nullable=false)
     */
    private $controlLevel;

    /**
     * @var string
     *
     * @ORM\Column(name="validation_status_label", type="string", length=191, nullable=true)
     */
    private $validationStatusLabel;

    /**
     * @var string
     *
     * @ORM\Column(name="agency", type="string", length=32, nullable=true)
     */
    private $agency;

    /**
     * @var integer
     *
     * @ORM\Column(name="revalidate", type="smallint", nullable=true)
     */
    private $revalidate = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="final_status", type="smallint", nullable=true)
     */
    private $finalStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=true)
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
     * @ORM\Column(name="id_greenpoint_attachment", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idGreenpointAttachment;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Attachment", inversedBy="greenpointAttachment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id")
     * })
     */
    private $idAttachment;



    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return GreenpointAttachment
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set validationStatus
     *
     * @param integer $validationStatus
     *
     * @return GreenpointAttachment
     */
    public function setValidationStatus($validationStatus)
    {
        $this->validationStatus = $validationStatus;

        return $this;
    }

    /**
     * Get validationStatus
     *
     * @return integer
     */
    public function getValidationStatus()
    {
        return $this->validationStatus;
    }

    /**
     * Set validationCode
     *
     * @param string $validationCode
     *
     * @return GreenpointAttachment
     */
    public function setValidationCode($validationCode)
    {
        $this->validationCode = $validationCode;

        return $this;
    }

    /**
     * Get validationCode
     *
     * @return string
     */
    public function getValidationCode()
    {
        return $this->validationCode;
    }

    /**
     * Set controlLevel
     *
     * @param integer $controlLevel
     *
     * @return GreenpointAttachment
     */
    public function setControlLevel($controlLevel)
    {
        $this->controlLevel = $controlLevel;

        return $this;
    }

    /**
     * Get controlLevel
     *
     * @return integer
     */
    public function getControlLevel()
    {
        return $this->controlLevel;
    }

    /**
     * Set validationStatusLabel
     *
     * @param string $validationStatusLabel
     *
     * @return GreenpointAttachment
     */
    public function setValidationStatusLabel($validationStatusLabel)
    {
        $this->validationStatusLabel = $validationStatusLabel;

        return $this;
    }

    /**
     * Get validationStatusLabel
     *
     * @return string
     */
    public function getValidationStatusLabel()
    {
        return $this->validationStatusLabel;
    }

    /**
     * Set agency
     *
     * @param string $agency
     *
     * @return GreenpointAttachment
     */
    public function setAgency($agency)
    {
        $this->agency = $agency;

        return $this;
    }

    /**
     * Get agency
     *
     * @return string
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * Set revalidate
     *
     * @param integer $revalidate
     *
     * @return GreenpointAttachment
     */
    public function setRevalidate($revalidate)
    {
        $this->revalidate = $revalidate;

        return $this;
    }

    /**
     * Get revalidate
     *
     * @return integer
     */
    public function getRevalidate()
    {
        return $this->revalidate;
    }

    /**
     * Set finalStatus
     *
     * @param integer $finalStatus
     *
     * @return GreenpointAttachment
     */
    public function setFinalStatus($finalStatus)
    {
        $this->finalStatus = $finalStatus;

        return $this;
    }

    /**
     * Get finalStatus
     *
     * @return integer
     */
    public function getFinalStatus()
    {
        return $this->finalStatus;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return GreenpointAttachment
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
     * @return GreenpointAttachment
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
     * Get idGreenpointAttachment
     *
     * @return integer
     */
    public function getIdGreenpointAttachment()
    {
        return $this->idGreenpointAttachment;
    }

    /**
     * Set idAttachment
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $idAttachment
     *
     * @return GreenpointAttachment
     */
    public function setIdAttachment(\Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $idAttachment = null)
    {
        $this->idAttachment = $idAttachment;

        return $this;
    }

    /**
     * Get idAttachment
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment
     */
    public function getIdAttachment()
    {
        return $this->idAttachment;
    }
}
