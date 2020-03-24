<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Defuse\Crypto\{Exception\EnvironmentIsBrokenException, Key};
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Traits\{BlamableAddedTrait, PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class FileVersion
{
    use ConstantsAwareTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use PublicizeIdentityTrait;

    public const FILE_SYSTEM_USER_ATTACHMENT    = 'user_attachment';
    public const FILE_SYSTEM_GENERATED_DOCUMENT = 'generated_document';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(length=191)
     *
     * @Groups({"fileVersion:read"})
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(length=191, nullable=true)
     *
     * @Groups({"fileVersion:read"})
     */
    private $originalName;

    /**
     * @var AttachmentSignature[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\AttachmentSignature", mappedBy="fileVersion")
     *
     * @Groups({"fileVersion:read"})
     */
    private $signatures;

    /**
     * The size of the file in bytes.
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Groups({"fileVersion:read"})
     */
    private $size;

    /**
     * @var Collection|FileDownload[]
     *
     * @ORM\OneToMany(targetEntity="FileDownload", fetch="EXTRA_LAZY", mappedBy="fileVersion", cascade={"persist", "remove"})
     */
    private $fileVersionDownloads;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\File", inversedBy="versions")
     * @ORM\JoinColumn(name="id_file", nullable=false)
     */
    private $file;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups({"fileVersion:read"})
     */
    private $fileSystem;

    /**
     * @var|null string
     *
     * @ORM\Column(length=512, nullable=true)
     */
    private $encryptionKey;

    /**
     * @var string|null
     */
    private $plainEncryptionKey;

    /**
     * @var|null string
     *
     * @ORM\Column(length=150, nullable=true)
     */
    private $mimeType;

    /**
     * @param string      $path
     * @param Staff       $addedBy
     * @param File        $file
     * @param string|null $plainEncryptionKey
     * @param string|null $mimeType
     *
     * @throws Exception
     */
    public function __construct(string $path, Staff $addedBy, File $file, ?string $plainEncryptionKey, ?string $mimeType)
    {
        $this->signatures           = new ArrayCollection();
        $this->fileVersionDownloads = new ArrayCollection();
        $this->path                 = $path;
        $this->file                 = $file;
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
        $this->plainEncryptionKey   = $plainEncryptionKey;
        $this->mimeType             = $mimeType;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return FileVersion
     */
    public function setPath(string $path): FileVersion
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param string $originalName
     *
     * @return FileVersion
     */
    public function setOriginalName(string $originalName): FileVersion
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * @return AttachmentSignature[]
     */
    public function getSignatures(): iterable
    {
        return $this->signatures;
    }

    /**
     * @return int
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @param int $size
     *
     * @return FileVersion
     */
    public function setSize(?int $size): FileVersion
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return Collection|FileDownload[]
     */
    public function getFileVersionDownloads()
    {
        return $this->fileVersionDownloads;
    }

    /**
     * @return string|null
     */
    public function getEncryptionKey(): ?string
    {
        return $this->encryptionKey;
    }

    /**
     * @param string|null $encryptionKey
     *
     * @return Attachment
     */
    public function setEncryptionKey(?string $encryptionKey): FileVersion
    {
        $this->encryptionKey = $encryptionKey;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlainEncryptionKey(): ?string
    {
        return $this->plainEncryptionKey;
    }

    /**
     * @param string|null $plainEncryptionKey
     *
     * @return Attachment
     */
    public function setPlainEncryptionKey(?string $plainEncryptionKey): FileVersion
    {
        $this->plainEncryptionKey = $plainEncryptionKey;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return File|null
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @return string|null
     */
    public function getFileSystem(): ?string
    {
        return $this->fileSystem;
    }

    /**
     * @param string $fileSystem
     *
     * @return FileVersion
     */
    public function setFileSystem(string $fileSystem): FileVersion
    {
        $this->fileSystem = $fileSystem;

        return $this;
    }
}
