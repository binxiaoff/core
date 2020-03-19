<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Attachment;

use Unilend\Entity\FileVersion;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\File\FileCreated;

class AttachmentCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param FileVersion $attachment
     */
    public function postPersist(FileVersion $attachment): void
    {
        $this->messageBus->dispatch(new FileCreated($attachment));
    }
}
