<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_folder", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_drive", "path_hash"})
 * })
 *
 * @UniqueEntity(fields={"drive", "path"})
 *
 * @ApiResource(
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         },
 *     },
 *     collectionOperations={}
 * )
 *
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(name="files",
 *         joinTable=@ORM\JoinTable(
 *             name="core_folder_file",
 *         )
 *     ),
 * })
 */
class Folder extends AbstractFolder
{
    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;

    /**
     * @var File[]|Collection
     *
     * This is a OneToMany unidirectionnal relation : Folder <-× File
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/reference/association-mapping.html#one-to-many-unidirectional-with-join-table
     *
     * @ORM\ManyToMany(targetEntity=File::class, cascade={"persist", "remove"}, indexBy="publicId")
     * @ORM\JoinTable(
     *     name="core_folder_file",
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected Collection $files;

    /**
     * @ORM\Column(type="text", nullable=false)
     *
     * @Groups({"folder:read"})
     */
    private string $path;

    /**
     * used for database unique index (path field is to big for unique index).
     * unfortunatly it is a hassle to handle virtual column with doctrine so I need to create a true column for this.
     *
     * @Assert\NotBlank
     * @Assert\Length(max="10")
     *
     * @ORM\Column(type="string", length=10, nullable=false)
     */
    private string $pathHash;

    /**
     * @Assert\Length(max="100")
     * @Assert\AtLeastOneOf({
     *     @Assert\NotBlank,
     *     @Assert\Expression("'/' == this.getPath()")
     * })
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     *
     * @Groups({"folder:read"})
     */
    private string $name;

    /**
     * @Assert\NotBlank
     *
     * @ORM\ManyToOne(targetEntity=Drive::class, inversedBy="folders", cascade={"persist"})
     * @ORM\JoinColumn(name="id_drive", nullable=false)
     */
    private Drive $drive;

    /**
     * @param string|null $parentPath
     */
    public function __construct(string $name, Drive $drive, string $parentPath = '/')
    {
        parent::__construct();
        $this->drive = $drive;

        if (null === $this->drive->getFolder($parentPath)) {
            throw new InvalidArgumentException(sprintf('Given path %s is not a folder in drive', $parentPath));
        }

        $this->name     = $name;
        $this->path     = ('/' === $parentPath ? '' : $parentPath) . DIRECTORY_SEPARATOR . $name;
        $this->pathHash = hash('crc32b', $this->path);
        $this->added    = new DateTimeImmutable();
        $this->setPublicId();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDrive(): Drive
    {
        return $this->drive;
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

    /**
     * @param string|Folder $relativePath
     */
    public function deleteFolder($relativePath): AbstractFolder
    {
        $this->drive->deleteFolder($this->normalizePath($relativePath));

        return $this;
    }

    public function getFile(string $path): ?File
    {
        return parent::getFile($this->normalizePath($path));
    }

    public function getFolder(string $relativePath): ?AbstractFolder
    {
        return $this->drive->getFolder($this->normalizePath($relativePath));
    }

    public function createFolder(string $path): AbstractFolder
    {
        $this->drive->createFolder($this->normalizePath($path));

        return $this;
    }

    /**
     * @param string|Folder $toDelete
     */
    public function delete($toDelete): Folder
    {
        $this->drive->delete($this->normalizePath($toDelete));

        return $this;
    }

    public function exist(string $path): bool
    {
        return $this->drive->exist($this->normalizePath($path));
    }

    public function get(string $path): ?object
    {
        return $this->drive->get($this->normalizePath($path));
    }

    /**
     * @return Collection|Folder[]
     */
    public function getFolders(?int $depth = null): Collection
    {
        if ($depth < 1) {
            throw new \InvalidArgumentException('The depth parameter must strictly be above 0');
        }

        return $this->drive->getFolders(count(explode(DIRECTORY_SEPARATOR, $this->path)) + (int) $depth - 1)
            ->filter(fn (Folder $folder) => 0 === mb_strpos($folder->getPath(), $this->path) && $this->path !== $folder->getPath())
        ;
    }

    public function deleteFile($path): Folder
    {
        if ($path instanceof File) {
            $this->removeFile($path);
        }

        $this->drive->deleteFile($this->normalizePath($path));

        return $this;
    }

    /**
     * @param string|Folder $test
     */
    private function assertDescendent($test)
    {
        if ($test instanceof self) {
            $test = $test->getPath();
        }

        if (str_starts_with(DIRECTORY_SEPARATOR, $test) && false === str_starts_with($test, $this->path)) {
            throw new \LogicException();
        }
    }

    /**
     * @param string|Folder $path
     */
    private function normalizePath($path)
    {
        $this->assertDescendent($path);

        if ($path instanceof self) {
            $path = $path->getPath();
        }

        $relativePath = 0 === mb_strpos($path, DIRECTORY_SEPARATOR) ? mb_substr($path, mb_strlen($this->path) + 1) : $path;

        return $this->path . DIRECTORY_SEPARATOR . $relativePath;
    }
}
