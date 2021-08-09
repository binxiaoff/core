<?php

declare(strict_types=1);

namespace KLS\Agency\MessageHandler;

use InvalidArgumentException;
use JsonException;
use KLS\Agency\Entity\Project;
use KLS\Agency\Message\ProjectStatusUpdated;
use KLS\Agency\Notifier\ProjectMemberNotifier;
use KLS\Agency\Repository\ProjectRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

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
