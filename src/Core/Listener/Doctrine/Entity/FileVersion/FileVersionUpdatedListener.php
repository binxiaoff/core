<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\FileVersion;

use LogicException;

class FileVersionUpdatedListener
{
    /**
     * @throws LogicException
     */
    public function blockUpdating(): void
    {
        throw new LogicException('FileVersion is an immutable object');
    }
}
