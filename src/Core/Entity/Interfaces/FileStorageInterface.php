<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Interfaces;

interface FileStorageInterface
{
    /**
     * @return string
     */
    public function getRelativeFilePath(): ?string;

    /**
     * @return mixed
     */
    public function setRelativeFilePath(?string $relativeFilePath);
}
