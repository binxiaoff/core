<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\File;

use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Message\File\ProjectFileUploaded;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Project\ProjectNotifier;

class ProjectFileUploadedHandler implements MessageHandlerInterface
{
    /** @var ProjectNotifier */
    private $projectNotifier;
    /** @var ProjectRepository */
    private $projectRepository;

    /**
     * @param ProjectRepository $projectRepository
     * @param ProjectNotifier   $projectNotifier
     */
    public function __construct(ProjectRepository $projectRepository, ProjectNotifier $projectNotifier)
    {
        $this->projectRepository = $projectRepository;
        $this->projectNotifier   = $projectNotifier;
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

        $this->projectNotifier->notifyUploaded($project);
    }
}
