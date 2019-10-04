<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Project;

use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Project\ProjectPublished;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Project\ProjectNotifier;

class ProjectPublishedHandler implements MessageHandlerInterface
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
     * @param ProjectPublished $projectRequested
     *
     * @throws Exception
     */
    public function __invoke(ProjectPublished $projectRequested)
    {
        $project = $this->projectRepository->find($projectRequested->getProjectId());
        if ($project) {
            $this->projectNotifier->notifyProjectPublished($project);
        }
    }
}
