<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\File;

use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\File\FileCreated;
use Unilend\Repository\FileVersionRepository;
use Unilend\Service\{File\FileNotifier};

class FileCreatedHandler implements MessageHandlerInterface
{
    /** @var FileVersionRepository */
    private $fileVersionRepository;
    /** @var FileNotifier */
    private $fileNotifier;

    /**
     * @param FileVersionRepository $fileVersionRepository
     * @param FileNotifier          $fileNotifier
     */
    public function __construct(FileVersionRepository $fileVersionRepository, FileNotifier $fileNotifier)
    {
        $this->fileVersionRepository = $fileVersionRepository;
        $this->fileNotifier          = $fileNotifier;
    }

    /**
     * @param FileCreated $fileCreated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(FileCreated $fileCreated)
    {
        $fileVersionId = $fileCreated->getFileVersionId();
        $fileVersion   = $this->fileVersionRepository->find($fileVersionId);

        if (null === $fileVersion) {
            throw new InvalidArgumentException(sprintf('The file with id %d does not exist', $fileVersionId));
        }

        $this->fileNotifier->notifyUploaded($fileVersion);
    }
}
