<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher;

use KLS\Core\Message\AsyncMessageInterface;

class PostFlushListener
{
    use MessageDispatcherTrait;

    private array $messages = [];

    public function postFlush(): void
    {
        foreach ($this->messages as $index => $message) {
            if ($message instanceof AsyncMessageInterface) {
                $this->messageBus->dispatch($message);
                unset($this->messages[$index]);
            }
        }
    }

    public function addMessage(AsyncMessageInterface $message): void
    {
        $this->messages[] = $message;
    }
}
