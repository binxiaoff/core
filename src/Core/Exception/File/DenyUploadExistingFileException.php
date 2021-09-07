<?php

declare(strict_types=1);

namespace KLS\Core\Exception\File;

use Exception;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\File;

class DenyUploadExistingFileException extends Exception
{
    public function __construct(FileInput $request, File $existingFile, object $targetEntity)
    {
        parent::__construct(\sprintf(
            'There is already a %s with id %s on the %s %s. You can only update its version.',
            $request->type,
            $existingFile->getPublicId(),
            \get_class($targetEntity),
            \method_exists($targetEntity, 'getPublicId') ? $targetEntity->getPublicId() : ''
        ));
    }
}
