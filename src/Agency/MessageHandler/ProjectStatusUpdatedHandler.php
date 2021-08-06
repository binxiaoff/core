<?php

declare(strict_types=1);

namespace Unilend\Agency\MessageHandler;

use InvalidArgumentException;
use JsonException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Message\ProjectStatusUpdated;
use Unilend\Agency\Notifier\ProjectMemberNotifier;
use Unilend\Agency\Repository\ProjectRepository;

class ProjectStatusUpdatedHandler implements MessageHandlerInterface
{
    private ProjectRepository $projectRepository;
    private ProjectMemberNotifier $projectMemberNotifier;

    public function __construct(
        ProjectMemberNotifier $projectMemberNotifier,
        ProjectRepository $projectRepository
    ) {
        $this->projectRepository     = $projectRepository;
        $this->projectMemberNotifier = $projectMemberNotifier;
    }

    /**
     * @throws JsonException
     */
    public function __invoke(ProjectStatusUpdated $projectStatusUpdated)
    {
        $project = $this->projectRepository->find($projectStatusUpdated->getProjectId());

        if (null === $project) {
            throw new InvalidArgumentException(\sprintf("Project with id %d doesn't exist", $projectStatusUpdated->getProjectId()));
        }

        if (Project::STATUS_DRAFT === $projectStatusUpdated->getPreviousStatus() && Project::STATUS_PUBLISHED === $projectStatusUpdated->getNextStatus()) {
            $this->onProjectPublication($project);
        }
    }

    /**
     * @throws JsonException
     */
    private function onProjectPublication(Project $project)
    {
        $this->notifyProjectPublication($project);
    }

    /**
     * @throws JsonException
     */
    private function notifyProjectPublication(Project $project)
    {
        foreach ($project->getMembers() as $projectMember) {
            $this->projectMemberNotifier->notifyProjectPublication($projectMember);
        }
    }
}
