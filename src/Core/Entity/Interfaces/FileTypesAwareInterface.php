<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Interfaces;

interface FileTypesAwareInterface
{
    /**
     * @return string[]
     */
    public static function getFileTypes(): array;
}
