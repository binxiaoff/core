<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\FileVersion;

class FileVersionUpdatedListener
{
    /**
     * @todo Throw an exception here: FileVersion cannot be updated (to be done when file refactoring is merged).
     */
    public function blockUpdating(): void
    {
    }
}
