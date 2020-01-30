<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Attachment;

use Unilend\Entity\Attachment;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\Attachment\AttachmentCreated;

class AttachmentCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param Attachment $attachment
     */
    public function postPersist(Attachment $attachment): void
    {
        $this->messageBus->dispatch(new AttachmentCreated($attachment));
    }
}
