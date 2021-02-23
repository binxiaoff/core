<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_folder", uniqueConstraints={
 *     @ORM\UniqueConstraint(columns={"id_drive", "path_hash"})
 * })
 *
 * @UniqueEntity(fields={"drive", "path"})
 */
class Folder extends AbstractFileContainer
{
    use TimestampableAddedOnlyTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private string $path;

    /**
     * used for database unique index (path field is to big for unique index)
     *
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(max="10")
     *
     * @ORM\Column(type="string", length=10, nullable=false)
     */
    private string $pathHash;

    /**
     * @var string
     *
     * @Assert\Length(max="100")
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private string $name;

    /**
     * @var Drive
     *
     * @Assert\NotBlank
     *
     * @ORM\ManyToOne(targetEntity=Drive::class, inversedBy="folders")
     * @ORM\JoinColumn(name="id_drive", nullable=false)
     */
    private Drive $drive;

    /**
     * @param string      $name
     * @param Drive       $drive
     * @param string|null $path
     */
    public function __construct(string $name, Drive $drive, ?string $path = null)
    {
        parent::__construct();
        $this->drive = $drive;
        $path = '/' === $path ? null : $path;

        if ($path && false === $this->drive->isPathPresent($path)) {
            throw new InvalidArgumentException(sprintf('Given path %s does not exist in drive', $path));
        }

        $this->name = $name;
        $this->path = $path . DIRECTORY_SEPARATOR . $name;
        $this->pathHash = hash('crc32b', $this->path);
        $this->added = new DateTimeImmutable();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Drive
     */
    public function getDrive(): Drive
    {
        return $this->drive;
    }

    /**
     * @return iterable|File[]
     */
    public function getFiles(): iterable
    {
        return $this->files;
    }
}
