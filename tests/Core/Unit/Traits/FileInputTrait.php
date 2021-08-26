<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use Faker\Provider\Base;
use KLS\Core\DTO\FileInput;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileInputTrait
{
    private function createFileInput($targetEntity, ?string $type = null): FileInput
    {
        $filePath         = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploadTestFile';
        $originalFileName = Base::asciify(\str_repeat('*', 20));
        \fopen($filePath, 'wb+');

        $uploadedFile = new UploadedFile($filePath, $originalFileName, null, null, true);

        return new FileInput($uploadedFile, $type ?? FileInput::ACCEPTED_MEDIA_TYPE[0], $targetEntity);
    }
}
