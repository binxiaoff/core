<?php

declare(strict_types=1);

namespace KLS\Core\Message\File;

use KLS\Core\Entity\File;
use KLS\Core\Message\AsyncMessageInterface;

class FileUploaded implements AsyncMessageInterface
{
    private int $fileId;
    private array $context;

    public function __construct(File $file, array $context)
    {
        $this->fileId  = $file->getId();
        $this->context = $context;
    }

    public function getFileId(): int
    {
        return $this->fileId;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
