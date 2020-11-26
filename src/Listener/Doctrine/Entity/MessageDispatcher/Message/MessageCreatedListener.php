<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Message;

use Unilend\Entity\Message;
use Unilend\Message\Message\MessageCreated;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

class MessageCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param Message $message
     */
    public function postPersist(Message $message): void
    {
        $this->messageBus->dispatch(new MessageCreated($message));
    }
}
