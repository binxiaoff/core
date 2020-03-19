<?php

declare(strict_types=1);

namespace Unilend\Message\File;

use Unilend\Entity\FileVersion;

class FileCreated
{
    /** @var int */
    private $fileVersionId;

    /**
     * @param FileVersion $fileVersion
     */
    public function __construct(FileVersion $fileVersion)
    {
        $this->fileVersionId = $fileVersion->getId();
    }

    /**
     * @return int
     */
    public function getFileVersionId(): int
    {
        return $this->fileVersionId;
    }
}
