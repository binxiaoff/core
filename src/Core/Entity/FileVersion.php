<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="core_file_version")
 *
 * @ApiResource(
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *         "download": {
 *             "method": "GET",
 *             "controller": "KLS\Core\Controller\File\Download",
 *             "path": "/core/file_versions/{publicId}/download/{type}",
 *         },
 *     },
 * )
 */
class FileVersion
{
    use ConstantsAwareTrait;
    use TimestampableTrait;
    use PublicizeIdentityTrait;

    public const FILE_SYSTEM_USER_ATTACHMENT    = 'user_attachment';
    public const FILE_SYSTEM_GENERATED_DOCUMENT = 'generated_document';

    /**
     * @ORM\Column(length=191)
     */
    private string $path;

    /**
     * @ORM\Column(length=191, nullable=true)
     *
     * @Groups({"fileVersion:read"})
     */
    private string $originalName;

    /**
     * @var FileVersionSignature[]
     *
     * @ORM\OneToMany(targetEntity="FileVersionSignature", mappedBy="fileVersion")
     *
     * @Groups({"fileVersion:read"})
     */
    private $signatures;

    /**
     * The size of the file in bytes.
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Groups({"fileVersion:read"})
     */
    private ?int $size = null;

    /**
     * @var Collection|FileDownload[]
     *
     * @ORM\OneToMany(targetEntity="FileDownload", fetch="EXTRA_LAZY", mappedBy="fileVersion")
     */
    private $fileVersionDownloads;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\File", inversedBy="fileVersions")
     * @ORM\JoinColumn(name="id_file", nullable=false)
     */
    private File $file;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $fileSystem;

    /**
     * @var string|null string
     *
     * @ORM\Column(length=512, nullable=true)
     */
    private ?string $encryptionKey;

    private ?string $plainEncryptionKey;

    /**
     * @var string|null string
     *
     * @ORM\Column(length=150, nullable=true)
     *
     * @Groups({"fileVersion:read"})
     */
    private ?string $mimeType;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\User")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id", nullable=false)
     */
    private User $addedBy;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=true)
     */
    private ?Company $company;

    public function __construct(string $path, User $addedBy, File $file, string $fileSystem, ?string $plainEncryptionKey = null, ?string $mimeType = null, ?Company $company = null)
    {
        $this->signatures           = new ArrayCollection();
        $this->fileVersionDownloads = new ArrayCollection();
        $this->path                 = $path;
        $this->file                 = $file;
        $this->addedBy              = $addedBy;
        $this->added                = new DateTimeImmutable();
        $this->fileSystem           = $fileSystem;
        $this->plainEncryptionKey   = $plainEncryptionKey;
        $this->encryptionKey        = null;
        $this->mimeType             = $mimeType;
        $this->company              = $company;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): FileVersion
    {
        $this->path = $path;

        return $this;
    }

    public function setOriginalName(string $originalName): FileVersion
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * @return FileVersionSignature[]
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

    public function getEncryptionKey(): ?string
    {
        return $this->encryptionKey;
    }

    public function setEncryptionKey(?string $encryptionKey): FileVersion
    {
        $this->encryptionKey = $encryptionKey;

        return $this;
    }

    public function getPlainEncryptionKey(): ?string
    {
        return $this->plainEncryptionKey;
    }

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

    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return $this
     */
    public function setFile(?File $file): FileVersion
    {
        $this->file = $file;

        return $this;
    }

    public function getFileSystem(): ?string
    {
        return $this->fileSystem;
    }

    public function setFileSystem(string $fileSystem): FileVersion
    {
        $this->fileSystem = $fileSystem;

        return $this;
    }

    /**
     * @Groups({"fileVersion:read"})
     */
    public function getVersionNumber(): int
    {
        $fileVersions = $this->getFile()->getFileVersions();

        return $fileVersions->indexOf($this) + 1;
    }

    public function getAddedBy(): User
    {
        return $this->addedBy;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }
}
