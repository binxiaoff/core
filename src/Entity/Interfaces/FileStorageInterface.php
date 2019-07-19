<?php

declare(strict_types=1);

namespace Unilend\Entity\Interfaces;

interface FileStorageInterface
{
    /**
     * @return string
     */
    public function getRelativeFilePath(): ?string;

    /**
     * @param string|null $relativeFilePath
     *
     * @return mixed
     */
    public function setRelativeFilePath(?string $relativeFilePath);
}
