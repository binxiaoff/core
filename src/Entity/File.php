<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ORM\Entity
 *
 * @ApiResource
 */
class File
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

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
     * @ORM\JoinColumn(name="id_current_version")
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
    public function addVersion(FileVersion $version): File
    {
        if (!$this->versions->contains($version)) {
            $version->setFile($this);
            $this->versions->add($version);
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
    public function setCurrentVersion(FileVersion $currentVersion): File
    {
        $this->currentVersion = $currentVersion;

        return $this;
    }
}
