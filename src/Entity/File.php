<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\{BlamableArchivedTrait, PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\FileRepository")
 */
class File
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableArchivedTrait;

    public const TYPE_PROJECT_CONFIDENTIALITY_DISCLAIMER = 'project_confidentiality_disclaimer';
    public const TYPE_PROJECT_DESCRIPTION                = 'project_description';

    /**
     * @var string
     *
     * @ORM\Column(length=191, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(length=191, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Unilend\Entity\FileVersion", mappedBy="file", orphanRemoval=true)
     */
    private $versions;

    /**
     * @ORM\OneToOne(targetEntity="Unilend\Entity\FileVersion", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $currentVersion;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->added    = new DateTimeImmutable();
        $this->versions = new ArrayCollection();
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return File
     */
    public function setName(string $name): File
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|FileVersion[]
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    /**
     * @param FileVersion $version
     *
     * @return $this
     */
    public function addVersion(FileVersion $version): self
    {
        if (!$this->versions->contains($version)) {
            $this->versions[] = $version;
            $version->setFile($this);
        }

        return $this;
    }

    /**
     * @param FileVersion $version
     *
     * @return $this
     */
    public function removeVersion(FileVersion $version): self
    {
        if ($this->versions->contains($version)) {
            $this->versions->removeElement($version);
            // set the owning side to null (unless already changed)
            if ($version->getFile() === $this) {
                $version->setFile(null);
            }
        }

        return $this;
    }

    /**
     * @return FileVersion|null
     */
    public function getCurrentVersion(): ?FileVersion
    {
        return $this->currentVersion;
    }

    /**
     * @param FileVersion $currentVersion
     *
     * @return $this
     */
    public function setCurrentVersion(FileVersion $currentVersion): self
    {
        $this->currentVersion = $currentVersion;

        return $this;
    }
}
