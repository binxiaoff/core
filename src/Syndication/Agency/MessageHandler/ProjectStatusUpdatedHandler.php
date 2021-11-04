<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\MessageHandler;

use Http\Client\Exception;
use InvalidArgumentException;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\ProjectStatusUpdated;
use KLS\Syndication\Agency\Notifier\ProjectClosedNotifier;
use KLS\Syndication\Agency\Notifier\ProjectMemberNotifier;
use KLS\Syndication\Agency\Notifier\ProjectPublishedNotifier;
use KLS\Syndication\Agency\Repository\ProjectRepository;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProjectStatusUpdatedHandler implements MessageHandlerInterface
{
    private ProjectRepository $projectRepository;
    private ProjectMemberNotifier $projectMemberNotifier;
    private ProjectClosedNotifier $projectClosedNotifier;
    private ProjectPublishedNotifier $projectPublishedNotifier;

    public function __construct(
        ProjectMemberNotifier $projectMemberNotifier,
        ProjectRepository $projectRepository,
        ProjectPublishedNotifier $projectPublishedNotifier,
        ProjectClosedNotifier $projectClosedNotifier
    ) {
        $this->projectRepository        = $projectRepository;
        $this->projectMemberNotifier    = $projectMemberNotifier;
        $this->projectPublishedNotifier = $projectPublishedNotifier;
        $this->projectClosedNotifier    = $projectClosedNotifier;
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws SlackApiException
     */
    public function __invoke(ProjectStatusUpdated $projectStatusUpdated)
    {
        $project = $this->projectRepository->find($projectStatusUpdated->getProjectId());

        if (null === $project) {
            throw new InvalidArgumentException(\sprintf(
                "Project with id %d doesn't exist",
                $projectStatusUpdated->getProjectId()
            ));
        }

        if (
            Project::STATUS_DRAFT === $projectStatusUpdated->getPreviousStatus()
            && Project::STATUS_PUBLISHED === $projectStatusUpdated->getNextStatus()
        ) {
            $this->onProjectPublication($project);
        }

        if (Project::STATUS_ARCHIVED === $projectStatusUpdated->getNextStatus()) {
            $this->notifyProjectClose($project);
        }
    }

    /**
     * @throws Exception
     * @throws SlackApiException
     * @throws \JsonException
     */
    private function onProjectPublication(Project $project): void
    {
        $this->notifyProjectPublication($project);
    }

    /**
     * @throws Exception
     * @throws SlackApiException
     * @throws \JsonException
     */
    private function notifyProjectPublication(Project $project): void
    {
        foreach ($project->getMembers() as $projectMember) {
            $this->projectMemberNotifier->notifyProjectPublication($projectMember);
        }

        $this->projectPublishedNotifier->notify($project);
    }

    /**
     * @throws Exception
     * @throws SlackApiException
     */
    private function notifyProjectClose(Project $project): void
    {
        $this->projectClosedNotifier->notify($project);
    }
}
