<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class AttachmentSignature
{
    use TimestampableTrait;
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;

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
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_signatory", referencedColumnName="id", nullable=false)
     * })
     */
    private $signatory;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * AttachmentSignature constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

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
     * @return Attachment
     */
    public function getAttachment(): Attachment
    {
        return $this->attachment;
    }

    /**
     * @param Staff $signatory
     *
     * @return AttachmentSignature
     */
    public function setSignatory(Staff $signatory): AttachmentSignature
    {
        $this->signatory = $signatory;

        return $this;
    }

    /**
     * @return Staff
     */
    public function getSignatory(): Staff
    {
        return $this->signatory;
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
}
