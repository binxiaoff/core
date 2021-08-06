<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\Message;

use Unilend\Core\Entity\Message;
use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Core\Message\Message\MessageCreated;

class MessageCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(Message $message): void
    {
        $this->messageBus->dispatch(new MessageCreated($message));
    }
}
