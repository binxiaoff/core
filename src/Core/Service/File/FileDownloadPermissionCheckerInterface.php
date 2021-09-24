<?php

declare(strict_types=1);

namespace KLS\Core\Service\File;

use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\User;

interface FileDownloadPermissionCheckerInterface
{
    public function check(FileDownload $fileDownload, User $user): bool;
}
