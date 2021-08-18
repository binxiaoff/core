<?php

declare(strict_types=1);

namespace KLS\Core\Service\File;

use KLS\Core\Entity\File;

interface FileDeleteInterface
{
    public function supports(string $type): bool;

    public function delete(File $file, string $type): void;
}
