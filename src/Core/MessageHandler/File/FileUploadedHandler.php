<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\File;

use KLS\Core\Message\File\FileUploaded;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class FileUploadedHandler implements MessageHandlerInterface
{
    /** @var iterable|FileUploadedNotifierInterface[] */
    private iterable $notifiers;

    public function __construct(iterable $notifiers)
    {
        $this->notifiers = $notifiers;
    }

    public function __invoke(FileUploaded $fileUploaded): void
    {
        $context = $fileUploaded->getContext();

        foreach ($this->notifiers as $notifier) {
            $notifier->notify($context);
        }
    }
}
