<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\Message;

use Unilend\Core\Entity\Message;
use Unilend\Core\Message\Message\MessageCreated;
use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

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
