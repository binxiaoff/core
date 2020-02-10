<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Project;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Project\ProjectCreated;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Project\ProjectNotifier;

class ProjectCreatedHandler implements MessageHandlerInterface
{
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var ProjectNotifier */
    private $projectNotifier;

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
     * @param ProjectCreated $projectCreated
     */
    public function __invoke(ProjectCreated $projectCreated)
    {
        $project = $this->projectRepository->find($projectCreated->getProjectId());

        if ($project) {
            $this->projectNotifier->notifyProjectCreated($project);
        }
    }
}
