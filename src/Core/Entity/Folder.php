<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
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
 */
class Folder
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
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id", unique=true)}
     * )
     */
    protected Collection $files;

    /**
     * @ORM\Column(type="text", nullable=false)
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
    public function __construct(string $name, Drive $drive, string $parentPath)
    {
        $this->drive = $drive;

        if ('/' !== $parentPath && null === $this->drive->getFolder($parentPath)) {
            throw new InvalidArgumentException(sprintf('Given path %s is not a folder in drive', $parentPath));
        }

        $this->name     = $name;
        $this->path     = ('/' === $parentPath ? '' : $parentPath) . DIRECTORY_SEPARATOR . $name;
        $this->pathHash = hash('crc32b', $this->path);
        $this->added    = new DateTimeImmutable();
        $this->files    = new ArrayCollection();
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
     * @return Collection|Folder[]
     */
    public function getChildrenFolders(): Collection
    {
        return $this->drive->getFolders()->filter(function (Folder $folder) {
            return $this !== $folder && 0 === mb_strpos($folder->getPath(), $this->getPath());
        });
    }

    /**
     * @Groups({"folder:read"})
     *
     * @MaxDepth(1)
     */
    public function getContent(): Collection
    {
        return new ArrayCollection([...array_values($this->getChildrenFolders()->toArray()), ...array_values($this->files->toArray())]);
    }

    /**
     * @param $name
     */
    public function mkdir($name): Folder
    {
        return $this->drive->mkFolder($name, $this->getPath());
    }

    public function rmFile(File $file)
    {
        $this->files->removeElement($file);
    }

    public function rmFolder(string $relativePath)
    {
        $this->drive->rmFolder($this->path . DIRECTORY_SEPARATOR . $relativePath);
    }

    public function getFile(string $publicId): ?File
    {
        return $this->files[$publicId];
    }
}
