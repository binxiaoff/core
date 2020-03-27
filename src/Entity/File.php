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
use Unilend\Entity\Traits\{BlamableArchivedTrait, PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ORM\Entity
 *
 * @Gedmo\SoftDeleteable(fieldName="archived")
 *
 * @ApiResource(
 *     input=FileInput::class,
 *     output=true
 * )
 */
class File
{
    use PublicizeIdentityTrait;
    use BlamableArchivedTrait;
    use TimestampableTrait;

    /**
     * @var string
     *
     * @ORM\Column(length=191, nullable=true)
     *
     * @Groups({"file:read"})
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Unilend\Entity\FileVersion", mappedBy="file", orphanRemoval=true)
     *
     * @Groups({"file:read"})
     */
    private $fileVersions;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Entity\FileVersion", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_current_file_version")
     *
     * @Groups({"file:read"})
     */
    private $currentFileVersion;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $archived;

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
     * @param DateTimeImmutable $archived
     *
     * @return File
     */
    public function setArchived(DateTimeImmutable $archived): File
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getArchived(): ?DateTimeImmutable
    {
        return $this->archived;
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
