<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Traits\ConstantsAwareTrait;

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
 *         },
 *         "download": {
 *             "method": "GET",
 *             "controller": "Unilend\Core\Controller\File\Download",
 *             "path": "/core/file_versions/{publicId}/download/{type}"
 *         }
 *     }
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
     * @var string
     *
     * @ORM\Column(length=191)
     */
    private string $path;

    /**
     * @var string
     *
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
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\File", inversedBy="fileVersions")
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

    /**
     * @var string|null
     */
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id", nullable=false)
     */
    private User $addedBy;

    /**
     * @var Company|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company")
     * @ORM\JoinColumn(name="id_company", referencedColumnName="id", nullable=true)
     */
    private ?Company $company;

    /**
     * @param string       $path
     * @param User         $addedBy
     * @param File         $file
     * @param string       $fileSystem
     * @param string|null  $plainEncryptionKey
     * @param string|null  $mimeType
     * @param Company|null $company
     */
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
     * @return FileVersion
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
     * @return FileVersion
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
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @param File|null $file
     *
     * @return $this
     */
    public function setFile(?File $file): FileVersion
    {
        $this->file = $file;

        return $this;
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

    /**
     * @Groups({"fileVersion:read"})
     *
     * @return int
     */
    public function getVersionNumber(): int
    {
        $fileVersions = $this->getFile()->getFileVersions();

        return $fileVersions->indexOf($this) + 1;
    }

    /**
     * @return User
     */
    public function getAddedBy(): User
    {
        return $this->addedBy;
    }

    /**
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }
}
