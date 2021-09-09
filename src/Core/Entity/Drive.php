<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Exception\Drive\FolderAlreadyExistsException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a collection of folders akin to a filesystem with a base folder (represented by itself) root who has path /.
 *
 * Mainly used in dataroom
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
 *     },
 * )
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
     * @ORM\OneToMany(targetEntity=Folder::class, indexBy="path", mappedBy="drive", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @Assert\Valid
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
            throw new InvalidArgumentException('The depth parameter must strictly be above 0');
        }

        return $this->folders->filter(fn (Folder $folder) => null === $depth || \count(\explode(DIRECTORY_SEPARATOR, $folder->getPath())) <= ($depth + 1));
    }

    /**
     * @throws FolderAlreadyExistsException
     */
    public function createFolder(string $path): self
    {
        $path = $this->canonicalizePath($path);

        if ($this->exist($path)) {
            return $this;
        }

        $explodedPath = \array_filter(\explode(DIRECTORY_SEPARATOR, $path));

        $lastItem = \array_pop($explodedPath);

        $parentPath = $this->canonicalizePath(\implode(DIRECTORY_SEPARATOR, $explodedPath));

        $this->createFolder($parentPath);

        // The verification of path existant is made in the constructor
        $folder = new Folder($lastItem, $this, $this->canonicalizePath($parentPath));

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
            if (0 === \mb_strpos($folder->getPath(), $path)) {
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

    public function delete($toDelete): self
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

    public function getPath(): string
    {
        return DIRECTORY_SEPARATOR;
    }

    private function canonicalizePath(string $path): string
    {
        return DIRECTORY_SEPARATOR . \trim($path, DIRECTORY_SEPARATOR);
    }
}
