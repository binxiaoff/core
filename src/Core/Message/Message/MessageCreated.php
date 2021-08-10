<?php

declare(strict_types=1);

namespace KLS\Core\Message\Message;

use KLS\Core\Entity\Message;
use KLS\Core\Message\AsyncMessageInterface;

class MessageCreated implements AsyncMessageInterface
{
    private ?int $messageId;

    public function __construct(Message $message)
    {
        $this->messageId = $message->getId();
    }

    /**
     * @return int
     */
    public function getMessageId(): ?int
    {
        return $this->messageId;
    }
}
