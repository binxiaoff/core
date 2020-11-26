<?php

declare(strict_types=1);

namespace Unilend\Message\Message;

use Unilend\Entity\Message;
use Unilend\Message\AsyncMessageInterface;

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
