<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Core\Entity\FileVersion;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
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
    private $id;

    /**
     * @var FileVersion
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\FileVersion", inversedBy="fileVersionDownloads")
     * @ORM\JoinColumn(name="id_file_version", nullable=false)
     */
    private $fileVersion;

    /**
     * @var string
     *
     * @ORM\Column(length=150)
     */
    private $type;

    /**
     * @param FileVersion $fileVersion
     * @param Staff       $addedBy
     * @param string      $type
     *
     * @throws Exception
     */
    public function __construct(FileVersion $fileVersion, Staff $addedBy, string $type)
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
}
