<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Project;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Project\ProjectRequested;
use Unilend\Repository\ProjectRepository;

class ProjectRequestedHandler implements MessageHandlerInterface
{
    /** @var ProjectRepository */
    private $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function __invoke(ProjectRequested $projectRequested)
    {
        $project = $this->projectRepository->find($projectRequested->getProjectId());
    }
}
