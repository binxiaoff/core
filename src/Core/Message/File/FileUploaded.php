<?php

declare(strict_types=1);

namespace Unilend\Core\Message\File;

use Unilend\Core\Entity\File;
use Unilend\Core\Message\AsyncMessageInterface;

class FileUploaded implements AsyncMessageInterface
{
    /** @var int */
    private $fileId;
    /** @var array */
    private $context;

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
