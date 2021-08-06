<?php

declare(strict_types=1);

namespace Unilend\Core\Message\Message;

use Unilend\Core\Entity\Message;
use Unilend\Core\Message\AsyncMessageInterface;

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
