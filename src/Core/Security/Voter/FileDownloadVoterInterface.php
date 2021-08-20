<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\User;

interface FileDownloadVoterInterface
{
    public function supports(FileDownload $fileDownload): bool;

    public function canCreate(FileDownload $fileDownload, User $user): bool;
}
