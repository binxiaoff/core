<?php

declare(strict_types=1);

namespace KLS\Core\DataTransformer;

use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\File;
use KLS\Core\Entity\User;

interface FileInputDataUploadInterface
{
    public function supports($targetEntity): bool;

    public function upload($targetEntity, FileInput $fileInput, User $user, ?File $file): File;
}
