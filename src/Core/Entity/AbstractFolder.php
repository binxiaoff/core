<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Exception\Drive\FolderAlreadyExistsException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractFolder
{
    /**
     * @var File[]|Collection
     *
     * This is a OneToMany unidirectionnal relation : (Folder|Drive) <-× File
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     *
     * @ORM\ManyToMany(targetEntity=File::class, cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id", unique=true)}
     * )
     *
     * @Assert\All({
     *     @Assert\Expression("value.getName()")
     * })
     * @Assert\Valid
     */
    protected Collection $files;

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    /**
     * TODO add depth parameter ?
     *
     * @return Collection|File[]
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): AbstractFolder
    {
        if (false === $this->exist($file->getName())) {
            $this->files->add($file);
        }

        return $this;
    }

    public function removeFile(File $file): AbstractFolder
    {
        $this->files->removeElement($file);

        return $this;
    }

    public function getFile(string $path): ?File
    {
        $parentFolder = $this->getFolder(\dirname($path));

        if ($parentFolder === $this) {
            return $this->files->filter(fn (File $file) => $file->getName() === \basename($path))->first() ?: null;
        }

        return $parentFolder ? $parentFolder->getFile(\basename($path)) : null;
    }

    abstract public function getFolder(string $path): ?AbstractFolder;

    /**
     * @return Folder[]|Collection
     */
    abstract public function getFolders(?int $depth = null): Collection;

    /**
     * Return calling AbstractFolder.
     *
     * @throws FolderAlreadyExistsException
     */
    abstract public function createFolder(string $path): AbstractFolder;

    /**
     * The deletion will only be done if $folder target or is a folder.
     *
     * @param string|Folder $folder
     */
    abstract public function deleteFolder($folder): AbstractFolder;

    /**
     * If a File is given, we only try to remove it from current folder.
     *
     * If a string is given we treat it as a path and try to delete the targetItem
     *
     * The deletion will only be done if path is a file
     *
     * @param string|File $path
     */
    abstract public function deleteFile($path): AbstractFolder;

    /**
     * @param string|File|Folder $toDelete
     */
    abstract public function delete($toDelete): AbstractFolder;

    abstract public function exist(string $path): bool;

    abstract public function get(string $path): ?object;

    public function list(?int $depth = 1): array
    {
        $files = $this->getFiles();

        $folders = \array_values($this->getFolders(1)->toArray());

        $result = [...$files, ...$folders];

        if ($depth > 1) {
            foreach ($folders as $folder) {
                $result = \array_merge($result, $folder->list($depth - 1));
            }
        }

        return $result;
    }

    /**
     * @Groups({"core:abstractFolder:read"})
     *
     * @MaxDepth(1)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     */
    public function getContent(): array
    {
        return $this->list();
    }

    /**
     * @Groups({"core:abstractFolder:read"})
     */
    abstract public function getPath(): string;
}
