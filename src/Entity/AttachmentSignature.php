<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": "attachmentSignature:read"},
 *     denormalizationContext={"groups": "attachmentSignature:write"},
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object"},
 *         "sign": {
 *             "security": "is_granted('sign', object)",
 *             "method": "POST",
 *             "controller": "Unilend\Controller\AttachmentSignature\Sign",
 *             "path": "/attachment_signatures/{id}/sign",
 *             "denormalization_context": {"groups": {"attachmentSignature:sign"}}
 *         }
 *     },
 *     collectionOperations={
 *         "post"
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class AttachmentSignature
{
    use TimestampableTrait;
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;
    use ConstantsAwareTrait;

    public const STATUS_PENDED         = 10;
    public const STATUS_REQUESTED      = 20;
    public const STATUS_REQUEST_FAILED = 25;
    public const STATUS_SIGNED         = 30;
    public const STATUS_REFUSED        = 40;

    /**
     * @var Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Attachment", inversedBy="signatures")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_attachment", nullable=false)
     * })
     *
     * @Groups({"attachmentSignature:write", "attachmentSignature:read"})
     */
    private $attachment;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_signatory", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"attachmentSignature:write", "attachmentSignature:read"})
     */
    private $signatory;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getStatuses")
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(length=100, nullable=true)
     */
    private $transactionNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(length=255, nullable=true)
     *
     * @Groups({ "attachmentSignature:read"})
     */
    private $signatureUrl;

    /**
     * AttachmentSignature constructor.
     *
     * @param Attachment $attachment
     * @param Staff      $signatory
     * @param Staff      $addedBy
     *
     * @throws Exception
     */
    public function __construct(Attachment $attachment, Staff $signatory, Staff $addedBy)
    {
        $this->attachment = $attachment;
        $this->signatory  = $signatory;
        $this->addedBy    = $addedBy;
        $this->status     = self::STATUS_PENDED;
        $this->added      = new DateTimeImmutable();
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

    /**
     * @return string|null
     */
    public function getTransactionNumber(): ?string
    {
        return $this->transactionNumber;
    }

    /**
     * @param string|null $transactionNumber
     *
     * @return AttachmentSignature
     */
    public function setTransactionNumber(?string $transactionNumber): AttachmentSignature
    {
        $this->transactionNumber = $transactionNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSignatureUrl(): ?string
    {
        return $this->signatureUrl;
    }

    /**
     * @param string|null $signatureUrl
     *
     * @return AttachmentSignature
     */
    public function setSignatureUrl(?string $signatureUrl): AttachmentSignature
    {
        $this->signatureUrl = $signatureUrl;

        return $this;
    }

    /**
     * @return iterable
     */
    public function getStatuses(): iterable
    {
        return self::getConstants('STATUS_');
    }
}
