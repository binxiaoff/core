<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GreenpointAttachment
 *
 * @ORM\Table(name="greenpoint_attachment")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class GreenpointAttachment
{
    const STATUS_VALIDATION_VALID = 9;

    /**
     * @var int
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
     * @ORM\Column(name="id_greenpoint_attachment", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idGreenpointAttachment;

    /**
     * @var \Unilend\Entity\Attachment
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Attachment", inversedBy="greenpointAttachment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id", nullable=false, unique=true)
     * })
     */
    private $idAttachment;

    /**
     * @var GreenpointAttachmentDetail
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\GreenpointAttachmentDetail", mappedBy="idGreenpointAttachment")
     */
    private $greenpointAttachmentDetail;

    /**
     * @param int|null $validationStatus
     *
     * @return GreenpointAttachment
     */
    public function setValidationStatus(?int $validationStatus): GreenpointAttachment
    {
        $this->validationStatus = $validationStatus;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getValidationStatus(): ?int
    {
        return $this->validationStatus;
    }

    /**
     * @param string|null $validationCode
     *
     * @return GreenpointAttachment
     */
    public function setValidationCode(?string $validationCode): GreenpointAttachment
    {
        $this->validationCode = $validationCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getValidationCode(): ?string
    {
        return $this->validationCode;
    }

    /**
     * @param string|null $validationStatusLabel
     *
     * @return GreenpointAttachment
     */
    public function setValidationStatusLabel(?string $validationStatusLabel): GreenpointAttachment
    {
        $this->validationStatusLabel = $validationStatusLabel;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getValidationStatusLabel(): ?string
    {
        return $this->validationStatusLabel;
    }

    /**
     * @param \DateTime $added
     *
     * @return GreenpointAttachment
     */
    public function setAdded(\DateTime $added): GreenpointAttachment
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime|null $updated
     *
     * @return GreenpointAttachment
     */
    public function setUpdated(?\DateTime $updated): GreenpointAttachment
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @return int
     */
    public function getIdGreenpointAttachment(): int
    {
        return $this->idGreenpointAttachment;
    }

    /**
     * @param Attachment $idAttachment
     *
     * @return GreenpointAttachment
     */
    public function setIdAttachment(Attachment $idAttachment): GreenpointAttachment
    {
        $this->idAttachment = $idAttachment;

        return $this;
    }

    /**
     * @return Attachment
     */
    public function getIdAttachment(): Attachment
    {
        return $this->idAttachment;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }

    /**
     * @return GreenpointAttachmentDetail|null
     */
    public function getGreenpointAttachmentDetail(): ?GreenpointAttachmentDetail
    {
        return $this->greenpointAttachmentDetail;
    }
}
