<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class AttachmentSignature
{
    use TimestampableTrait;

    public const STATUS_PENDING = 0;
    public const STATUS_SIGNED  = 1;
    public const STATUS_REFUSED = 2;

    /**
     * @var Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Attachment", inversedBy="signatures")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_attachment", nullable=false)
     * })
     */
    private $attachment;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_signatory", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $signatory;

    /**
     * @var int
     *
     * @ORM\Column(name="docusign_envelope_id", type="integer", nullable=true)
     */
    private $docusignEnvelopeId;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @param Attachment $attachment
     *
     * @return AttachmentSignature
     */
    public function setAttachment(Attachment $attachment): AttachmentSignature
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return Attachment|null
     */
    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    /**
     * @param Clients $signatory
     *
     * @return AttachmentSignature
     */
    public function setSignatory(Clients $signatory): AttachmentSignature
    {
        $this->signatory = $signatory;

        return $this;
    }

    /**
     * @return Clients|null
     */
    public function getSignatory(): ?Clients
    {
        return $this->signatory;
    }

    /**
     * @param int|null $docusignEnvelopeId
     *
     * @return AttachmentSignature
     */
    public function setDocusignEnvelopeId(?int $docusignEnvelopeId): AttachmentSignature
    {
        $this->docusignEnvelopeId = $docusignEnvelopeId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDocusignEnvelopeId(): ?int
    {
        return $this->docusignEnvelopeId;
    }

    /**
     * @param int $status
     *
     * @return AttachmentSignature
     */
    public function setStatus(int $status): AttachmentSignature
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
