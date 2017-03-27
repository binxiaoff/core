<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GreenpointAttachment
 *
 * @ORM\Table(name="greenpoint_attachment", uniqueConstraints={@ORM\UniqueConstraint(name="id_attachment", columns={"id_attachment"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class GreenpointAttachment
{
    const STATUS_VALIDATION_VALID = 9;

    /**
     * @var integer
     *
     * @ORM\Column(name="validation_status", type="integer", nullable=true)
     */
    private $validationStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="validation_code", type="string", length=2, nullable=true)
     */
    private $validationCode;

    /**
     * @var string
     *
     * @ORM\Column(name="validation_status_label", type="string", length=191, nullable=true)
     */
    private $validationStatusLabel;

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
     * @var GreenpointAttachment
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachmentDetail", mappedBy="idGreenpointAttachment")
     */
    private $greenpointAttachmentDetail;

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
     * Get greenpointAttachmentDetail
     *
     * @return GreenpointAttachment
     */
    public function getGreenpointAttachmentDetail()
    {
        return $this->greenpointAttachmentDetail;
    }
}
