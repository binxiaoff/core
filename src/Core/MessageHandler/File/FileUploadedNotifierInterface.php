<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\File;

interface FileUploadedNotifierInterface
{
    public function notify(array $context): int;
}
