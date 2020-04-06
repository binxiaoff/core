<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\File;

use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\File\ProjectFileUploaded;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\{File\FileNotifier};

class ProjectFileUploadedHandler implements MessageHandlerInterface
{
    /** @var FileNotifier */
    private $fileNotifier;
    /** @var ProjectRepository */
    private $projectRepository;

    /**
     * @param ProjectRepository $projectRepository
     * @param FileNotifier      $fileNotifier
     */
    public function __construct(ProjectRepository $projectRepository, FileNotifier $fileNotifier)
    {
        $this->projectRepository = $projectRepository;
        $this->fileNotifier      = $fileNotifier;
    }

    /**
     * @param ProjectFileUploaded $projectFileUploaded
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(ProjectFileUploaded $projectFileUploaded)
    {
        $projectId = $projectFileUploaded->getProjectId();
        $project   = $this->projectRepository->find($projectId);

        if (null === $project) {
            throw new InvalidArgumentException(sprintf('The project with id %d does not exist', $projectId));
        }

        $this->fileNotifier->notifyUploaded($project);
    }
}
