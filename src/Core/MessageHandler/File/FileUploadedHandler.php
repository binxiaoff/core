<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\File;

use InvalidArgumentException;
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
            if ($notifier->allowsToNotify($context)) {
                $notifier->notify($context);

                return;
            }
        }

        throw new InvalidArgumentException('The context is not supported by any notifier');
    }
}
