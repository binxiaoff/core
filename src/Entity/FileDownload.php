<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\FileVersion", inversedBy="fileVersionDownloads")
     * @ORM\JoinColumn(name="id_file_version", nullable=false)
     */
    private $fileVersion;

    /**
     * @param FileVersion $fileVersion
     * @param Staff       $addedBy
     *
     * @throws Exception
     */
    public function __construct(FileVersion $fileVersion, Staff $addedBy)
    {
        $this->fileVersion = $fileVersion;
        $this->addedBy     = $addedBy;
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
}
