<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\Timestampable;

/**
 * @ORM\Table(name="project_attachment_signature")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectAttachmentSignature
{
    use Timestampable;

    public const STATUS_PENDING = 0;
    public const STATUS_SIGNED  = 1;
    public const STATUS_REFUSED = 2;

    /**
     * @var ProjectAttachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectAttachment", inversedBy="signatures")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project_attachment", referencedColumnName="id", nullable=false)
     * })
     */
    private $projectAttachment;

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
     * @param ProjectAttachment $projectAttachment
     *
     * @return ProjectAttachmentSignature
     */
    public function setProjectAttachment(ProjectAttachment $projectAttachment): ProjectAttachmentSignature
    {
        $this->projectAttachment = $projectAttachment;

        return $this;
    }

    /**
     * @return ProjectAttachment
     */
    public function getProjectAttachment(): ProjectAttachment
    {
        return $this->projectAttachment;
    }

    /**
     * @param Clients $signatory
     *
     * @return ProjectAttachmentSignature
     */
    public function setSignatory(Clients $signatory): ProjectAttachmentSignature
    {
        $this->signatory = $signatory;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getSignatory(): Clients
    {
        return $this->signatory;
    }

    /**
     * @param int|null $docusignEnvelopeId
     *
     * @return ProjectAttachmentSignature
     */
    public function setDocusignEnvelopeId(?int $docusignEnvelopeId): ProjectAttachmentSignature
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
     * @return ProjectAttachmentSignature
     */
    public function setStatus(int $status): ProjectAttachmentSignature
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
