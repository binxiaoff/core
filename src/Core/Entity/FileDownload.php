<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_file_download")
 */
class FileDownload
{
    use BlamableAddedTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @var FileVersion
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\FileVersion", inversedBy="fileVersionDownloads")
     * @ORM\JoinColumn(name="id_file_version", nullable=false)
     */
    private FileVersion $fileVersion;

    /**
     * @var string
     *
     * @ORM\Column(length=150)
     */
    private string $type;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id", nullable=false)
     */
    private $addedBy;

    /**
     * @param FileVersion $fileVersion
     * @param User        $addedBy
     * @param string      $type
     *
     */
    public function __construct(FileVersion $fileVersion, User $addedBy, string $type)
    {
        $this->fileVersion = $fileVersion;
        $this->addedBy     = $addedBy;
        $this->type        = $type;
        $this->added       = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return FileVersion
     */
    public function getFileVersion(): FileVersion
    {
        return $this->fileVersion;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return User
     */
    public function getAddedBy(): User
    {
        return $this->addedBy;
    }
}
