<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Traits\{ArchivableTrait, BlamableArchivedTrait, PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ORM\Entity
 *
 * @Gedmo\SoftDeleteable(fieldName="archived")
 *
 * @ApiResource(
 *     normalizationContext={"groups": {"file:read", "fileVersion:read", "timestampable:read"}},
 *     collectionOperations={
 *         "post": {
 *             "controller": "Unilend\Controller\File\Upload",
 *             "path": "/files/upload",
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
 *             "controller": "Unilend\Controller\File\Upload",
 *             "path": "/files/{id}/file_versions/upload",
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
 *             "controller": "Unilend\Controller\File\Delete",
 *             "path": "/files/{id}/{type}",
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
     * @var string|null
     *
     * @ORM\Column(length=191, nullable=true)
     *
     * @Groups({"file:read"})
     */
    private ?string $description;

    /**
     * @var FileVersion[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\FileVersion", mappedBy="file")
     * @ORM\OrderBy({"added": "ASC"})
     *
     * @Groups({"file:read"})
     */
    private Collection $fileVersions;

    /**
     * @var FileVersion
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\FileVersion", cascade={"persist"})
     * @ORM\JoinColumn(name="id_current_file_version")
     *
     * @Groups({"file:read"})
     */
    private FileVersion $currentFileVersion;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->added        = new DateTimeImmutable();
        $this->fileVersions = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return File
     */
    public function setDescription(?string $description): File
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|FileVersion[]
     */
    public function getFileVersions(): Collection
    {
        return $this->fileVersions;
    }

    /**
     * @return FileVersion|null
     */
    public function getCurrentFileVersion(): ?FileVersion
    {
        return $this->currentFileVersion;
    }

    /**
     * @param FileVersion $fileVersion
     *
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
     * @param FileVersion $version
     *
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
