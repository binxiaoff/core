<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\Message;

use KLS\Core\Entity\Message;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\PostFlushListener;
use KLS\Core\Message\Message\MessageCreated;

class MessageCreatedListener
{
    private PostFlushListener $postFlushListener;

    public function __construct(PostFlushListener $postFlushListener)
    {
        $this->postFlushListener = $postFlushListener;
    }

    public function postPersist(Message $message): void
    {
        $this->postFlushListener->addMessage(new MessageCreated($message));
    }
}
