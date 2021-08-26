<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use KLS\Core\Entity\Traits\BlamableAddedTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "fileVersionSignature:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "fileVersionSignature:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object"},
 *         "sign": {
 *             "security": "is_granted('sign', object)",
 *             "method": "POST",
 *             "controller": "KLS\Core\Controller\FileVersionSignature\Sign",
 *             "path": "/core/file_version_signatures/{publicId}/sign",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "fileVersionSignature:sign",
 *                 },
 *                 "openapi_definition_name": "sign",
 *             },
 *         },
 *     },
 *     collectionOperations={
 *         "post",
 *     },
 * )
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="core_file_version_signature")
 */
class FileVersionSignature
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
     * @var FileVersion
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\FileVersion", inversedBy="signatures")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_file_version", nullable=false)
     * })
     *
     * @Groups({"fileVersionSignature:write", "fileVersionSignature:read"})
     */
    private $fileVersion;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Staff")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_signatory", referencedColumnName="id", nullable=false)
     * })
     *
     * @Groups({"fileVersionSignature:write", "fileVersionSignature:read"})
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
     * @Groups({"fileVersionSignature:read"})
     */
    private $signatureUrl;

    /**
     * @throws Exception
     */
    public function __construct(FileVersion $fileVersion, Staff $signatory, Staff $addedBy)
    {
        $this->fileVersion = $fileVersion;
        $this->signatory   = $signatory;
        $this->addedBy     = $addedBy;
        $this->status      = self::STATUS_PENDED;
        $this->added       = new DateTimeImmutable();
    }

    public function setFileVersion(FileVersion $fileVersion): FileVersionSignature
    {
        $this->fileVersion = $fileVersion;

        return $this;
    }

    /**
     * @return FileVersion
     */
    public function getFileVersion(): ?FileVersion
    {
        return $this->fileVersion;
    }

    public function setSignatory(Staff $signatory): FileVersionSignature
    {
        $this->signatory = $signatory;

        return $this;
    }

    public function getSignatory(): Staff
    {
        return $this->signatory;
    }

    public function setStatus(int $status): FileVersionSignature
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getTransactionNumber(): ?string
    {
        return $this->transactionNumber;
    }

    public function setTransactionNumber(?string $transactionNumber): FileVersionSignature
    {
        $this->transactionNumber = $transactionNumber;

        return $this;
    }

    public function getSignatureUrl(): ?string
    {
        return $this->signatureUrl;
    }

    public function setSignatureUrl(?string $signatureUrl): FileVersionSignature
    {
        $this->signatureUrl = $signatureUrl;

        return $this;
    }

    public function getStatuses(): iterable
    {
        return self::getConstants('STATUS_');
    }
}
