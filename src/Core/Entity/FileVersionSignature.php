<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     normalizationContext={"groups": "fileVersionSignature:read"},
 *     denormalizationContext={"groups": "fileVersionSignature:write"},
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object"},
 *         "sign": {
 *             "security": "is_granted('sign', object)",
 *             "method": "POST",
 *             "controller": "Unilend\Controller\FileVersionSignature\Sign",
 *             "path": "/file_version_signatures/{id}/sign",
 *             "denormalization_context": {"groups": {"fileVersionSignature:sign"}}
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
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\FileVersion", inversedBy="signatures")
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
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Staff")
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
     * @param FileVersion $fileVersion
     * @param Staff       $signatory
     * @param Staff       $addedBy
     *
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

    /**
     * @param FileVersion $fileVersion
     *
     * @return FileVersionSignature
     */
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

    /**
     * @param Staff $signatory
     *
     * @return FileVersionSignature
     */
    public function setSignatory(Staff $signatory): FileVersionSignature
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
     * @return FileVersionSignature
     */
    public function setStatus(int $status): FileVersionSignature
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
     * @return FileVersionSignature
     */
    public function setTransactionNumber(?string $transactionNumber): FileVersionSignature
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
     * @return FileVersionSignature
     */
    public function setSignatureUrl(?string $signatureUrl): FileVersionSignature
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
