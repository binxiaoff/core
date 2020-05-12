<?php

declare(strict_types=1);

namespace Unilend\Message\File;

use Unilend\Entity\File;

class FileUploaded
{
    /** @var int */
    private $fileId;
    /** @var array */
    private $context;

    /**
     * @param File  $file
     * @param array $context
     */
    public function __construct(File $file, array $context)
    {
        $this->fileId  = $file->getId();
        $this->context = $context;
    }

    /**
     * @return int
     */
    public function getFileId(): int
    {
        return $this->fileId;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
