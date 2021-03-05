<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent a collection of folders akin to a filesystem with a base folder root who has path /.
 *
 * Mainly used in dataroom
 *
 * @ORM\Entity
 * @ORM\Table(name="core_drive")
 */
class Drive
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $id = null;

    /**
     * @var Folder[]|Collection
     *
     * @Assert\Count(min="1")
     *
     * @ORM\OneToMany(targetEntity=Folder::class, indexBy="path", mappedBy="drive", cascade={"persist", "remove"})
     */
    private Collection $folders;

    /**
     * construct.
     */
    public function __construct()
    {
        $this->folders = new ArrayCollection(['/' => new Folder('', $this, '/')]);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Folder[]
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    public function mkFolder(string $name, string $path = '/'): Folder
    {
        if ($this->get($path)) {
            throw new InvalidArgumentException('Path already exist');
        }

        $folder = new Folder($name, $this, $path);

        $this->folders[$folder->getPath()] = $folder;

        return $folder;
    }

    /**
     * @param Folder|string $path
     */
    public function rmFolder($path): Drive
    {
        if ($path instanceof Folder) {
            if ($path->getDrive() !== $this) {
                throw new InvalidArgumentException('The given folder drive is not $this');
            }

            $path = $path->getPath();
        }

        if ('/' === $path) {
            throw new InvalidArgumentException('Please delete the drive instead');
        }

        foreach ($this->folders as $folder) {
            if (0 === mb_strpos($path, $path->getPath())) {
                unset($this->folders[$folder->getPath()]);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function rmFile(File $file, $path = '/'): Drive
    {
        if ($path instanceof Folder) {
            $path = $path->getPath();
        }

        $folder = $this->getFolder($path);

        if ($folder) {
            $folder->rmFile($file);
        }

        return $this;
    }

    public function isPathPresent(string $path): bool
    {
        return '/' === $path || isset($this->folders[$path]);
    }

    public function getFolder(string $path): ?Folder
    {
        return $this->folders[$path] ?? null;
    }

    public function getFile(string $path): ?File
    {
        $parent = $this->getFolder(\dirname($path));

        if ($parent) {
            return $parent->getFile(basename($path));
        }

        return null;
    }

    /**
     * @return File|Folder|null
     */
    public function get(string $path)
    {
        return $this->getFolder($path) ?? $this->getFile($path) ?? null;
    }
}
