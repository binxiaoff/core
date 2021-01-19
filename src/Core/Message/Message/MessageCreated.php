<?php

declare(strict_types=1);

namespace Unilend\Core\Message\Message;

use Unilend\Core\Entity\Message;
use Unilend\Core\Message\AsyncMessageInterface;

class MessageCreated implements AsyncMessageInterface
{
    /**
     * @var int|null
     */
    private ?int $messageId;

    /**
     * MessageCreated constructor.
     *
     * @param Message $message
     */
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

