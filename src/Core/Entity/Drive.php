<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * Represent a collection of folders akin to a filesystem with a base folder root who has path /.
 *
 * Mainly used in dataroom
 *
 * @ORM\Entity
 * @ORM\Table(name="core_drive")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(name="files",
 *         joinTable=@ORM\JoinTable(
 *             name="core_drive_file",
 *         )
 *     ),
 * })
 */
class Drive extends AbstractFolder
{
    use PublicizeIdentityTrait;

    /**
     * @var Folder[]|Collection
     *
     * @Assert\Count(min="1")
     *
     * @ORM\OneToMany(targetEntity=Folder::class, indexBy="path", mappedBy="drive", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $folders;

    public function __construct()
    {
        parent::__construct();
        $this->folders = new ArrayCollection();
    }

    /**
     * @return Collection|Folder[]
     */
    public function getFolders(?int $depth = null): Collection
    {
        if ($depth < 1 && null !== $depth) {
            throw new \InvalidArgumentException('The depth parameter must strictly be above 0');
        }

        return $this->folders->filter(fn (Folder $folder) => null === $depth || count(explode(DIRECTORY_SEPARATOR, $folder->getPath())) <= ($depth + 1));
    }

    public function createFolder(string $path): Drive
    {
        $path = $this->canonicalizePath($path);

        $explodedPath = array_filter(explode(DIRECTORY_SEPARATOR, $path));

        if (empty($explodedPath)) {
            return $this;
        }

        $lastItem = array_pop($explodedPath);

        $parentPath = implode(DIRECTORY_SEPARATOR, $explodedPath);

        $this->createFolder($parentPath);

        if ($this->exist($path)) {
            throw new InvalidArgumentException('Path already exist');
        }

        $folder = new Folder($lastItem, $this, DIRECTORY_SEPARATOR . $parentPath);

        $this->folders[$folder->getPath()] = $folder;

        return $this;
    }

    /**
     * @param Folder|string $path
     */
    public function deleteFolder($path): Drive
    {
        if (DIRECTORY_SEPARATOR === $path) {
            throw new InvalidArgumentException('Please delete the drive instead');
        }

        if ($path instanceof Folder) {
            if ($path->getDrive() !== $this) {
                throw new InvalidArgumentException('The given folder drive is not $this');
            }

            $path = $path->getPath();
        }

        $path = $this->canonicalizePath($path);

        foreach ($this->folders as $folder) {
            if (0 === mb_strpos($folder->getPath(), $path)) {
                unset($this->folders[$folder->getPath()]);
            }
        }

        return $this;
    }

    public function getFolder(string $path): ?AbstractFolder
    {
        if (DIRECTORY_SEPARATOR === $path) {
            return $this;
        }

        $path = $this->canonicalizePath($path);

        return $this->folders[$path] ?? null;
    }

    /**
     * @return File|Folder|null
     */
    public function get(string $path): ?object
    {
        $path = $this->canonicalizePath($path);

        $folder = $this->getFolder($path);

        if ($folder) {
            return $folder;
        }

        $parentFolder = $this->getFolder(\dirname($path));

        if (null === $parentFolder) {
            return null;
        }

        return $parentFolder->getFile($path);
    }

    public function getFile(string $path): ?File
    {
        return parent::getFile($this->canonicalizePath($path));
    }

    public function exist(string $path): bool
    {
        if (DIRECTORY_SEPARATOR === $path) {
            return true;
        }

        $path = $this->canonicalizePath($path);

        if (isset($this->folders[$path])) {
            return true;
        }

        $parentPath = \dirname($path);

        $parentFolder = DIRECTORY_SEPARATOR === $parentPath ? $this : $this->folders[$parentPath];

        return $parentFolder && $parentFolder->getFile($path);
    }

    public function delete($toDelete): Drive
    {
        if (DIRECTORY_SEPARATOR === $toDelete) {
            throw new \LogicException('You cannot delete /, delete the drive instead');
        }

        // If file is given, we remove from the files of the folder (we do not go into subfolders)
        // This is because we do not and do not want have a bi directionnal relationship with File
        if ($toDelete instanceof File) {
            $this->removeFile($toDelete);
        }

        $parentFolder = \is_string($toDelete) ? $this->getFolder(\dirname($toDelete)) : null;

        $toDelete = \is_string($toDelete) ? $this->get($toDelete) : $toDelete;

        if ($toDelete instanceof Folder) {
            $this->deleteFolder($toDelete);
        }

        if ($toDelete instanceof File && $parentFolder) {
            $parentFolder->removeFile($toDelete);

            return $this;
        }

        return $this;
    }

    /**
     * @Groups({"folder:read"})
     *
     * @MaxDepth(1)
     */
    public function getContent(): array
    {
        return $this->list();
    }

    public function deleteFile($path): AbstractFolder
    {
        if ($path instanceof File) {
            $this->removeFile($path);
        }

        $parentFolder = \is_string($path) ? $this->getFolder(\dirname($path)) : null;

        if ($path instanceof File && $parentFolder) {
            $parentFolder->removeFile($path);
        }

        return $this;
    }

    private function canonicalizePath($path)
    {
        return DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
    }
}
