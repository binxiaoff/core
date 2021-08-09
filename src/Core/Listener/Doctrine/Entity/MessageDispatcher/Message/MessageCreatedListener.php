<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\Message;

use KLS\Core\Entity\Message;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Core\Message\Message\MessageCreated;

class MessageCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(Message $message): void
    {
        $this->messageBus->dispatch(new MessageCreated($message));
    }
}
