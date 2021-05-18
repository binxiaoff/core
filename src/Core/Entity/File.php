<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\ArchivableTrait;
use Unilend\Core\Entity\Traits\BlamableArchivedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_file")
 *
 * @Gedmo\SoftDeleteable(fieldName="archived")
 *
 * @ApiResource(
 *     normalizationContext={"groups": {"file:read", "fileVersion:read", "timestampable:read"}},
 *     collectionOperations={
 *         "post": {
 *             "controller": "Unilend\Core\Controller\File\Upload",
 *             "path": "/core/files/upload",
 *             "deserialize": false,
 *             "swagger_context": {
 *                 "consumes": {"multipart/form-data"},
 *                 "parameters": {
 *                     {
 *                         "in": "formData",
 *                         "name": "file",
 *                         "type": "file",
 *                         "description": "The uploaded file",
 *                         "required": true
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "type",
 *                         "type": "string",
 *                         "description": "The file type",
 *                         "required": true
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "targetEntity",
 *                         "type": "string",
 *                         "description": "The target entity as an IRI",
 *                         "required": true
 *                     }
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "upload_file_version": {
 *             "method": "POST",
 *             "controller": "Unilend\Core\Controller\File\Upload",
 *             "path": "/core/files/{publicId}/file_versions/upload",
 *             "deserialize": false,
 *             "swagger_context": {
 *                 "consumes": {"multipart/form-data"},
 *                 "parameters": {
 *                     {
 *                         "in": "formData",
 *                         "name": "file",
 *                         "type": "file",
 *                         "description": "The uploaded file",
 *                         "required": true
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "type",
 *                         "type": "string",
 *                         "description": "The file type",
 *                         "required": true
 *                     },
 *                     {
 *                         "in": "formData",
 *                         "name": "targetEntity",
 *                         "type": "string",
 *                         "description": "The target entity as an IRI",
 *                         "required": true
 *                     }
 *                 }
 *             }
 *         },
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *         "delete": {
 *             "controller": "Unilend\Core\Controller\File\Delete",
 *             "path": "/core/files/{publicId}/{type}",
 *         },
 *     }
 * )
 */
class File
{
    use PublicizeIdentityTrait;
    use BlamableArchivedTrait;
    use TimestampableTrait;
    use ArchivableTrait;

    /**
     * @var FileVersion[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\FileVersion", mappedBy="file")
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"file:read"})
     */
    private Collection $fileVersions;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Core\Entity\FileVersion", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_file_version")
     *
     * @Groups({"file:read"})
     */
    private ?FileVersion $currentFileVersion = null;

    /**
     * Name is nullable for now because they are used in arrangement
     * Name is needed because we need the client name.
     *
     * @ORM\Column(type="string", length=191, nullable=true)
     *
     * @Assert\Regex(pattern="#[^\/]+#")
     * @Assert\Length(max=191)
     */
    private ?string $name;

    /**
     * @throws Exception
     */
    public function __construct(?string $name = null)
    {
        $this->added        = new DateTimeImmutable();
        $this->fileVersions = new ArrayCollection();
        $this->name         = $name;
    }

    /**
     * @return Collection|FileVersion[]
     */
    public function getFileVersions(): Collection
    {
        return $this->fileVersions;
    }

    public function getCurrentFileVersion(): ?FileVersion
    {
        return $this->currentFileVersion;
    }

    /**
     * @return $this
     */
    public function setCurrentFileVersion(FileVersion $fileVersion): File
    {
        $currentFileVersion = $this->currentFileVersion;

        if (null === $currentFileVersion || $currentFileVersion->getPath() !== $fileVersion->getPath()) {
            $this->currentFileVersion = $fileVersion;
            $this->addVersion($fileVersion);
        }

        return $this;
    }

    /**
     * @Groups({"file:read"})
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @Groups({"file:read"})
     */
    public function getMimeType(): ?string
    {
        $currentFileVersion = $this->getCurrentFileVersion();

        if ($currentFileVersion) {
            return $currentFileVersion->getMimeType();
        }

        return null;
    }

    /**
     * @return $this
     */
    private function addVersion(FileVersion $version): File
    {
        if (!$this->fileVersions->contains($version)) {
            $version->setFile($this);
            $this->fileVersions->add($version);
        }

        return $this;
    }
}
