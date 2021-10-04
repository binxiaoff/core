<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\MessageHandler;

use KLS\Syndication\Agency\Message\ProjectCreated;
use KLS\Syndication\Agency\Notifier\ProjectCreatedNotifier;
use KLS\Syndication\Agency\Repository\ProjectRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProjectCreatedHandler implements MessageHandlerInterface
{
    private ProjectRepository $projectRepository;
    private ProjectCreatedNotifier $projectCreatedNotifier;

    public function __construct(ProjectRepository $projectRepository, ProjectCreatedNotifier $projectCreatedNotifier)
    {
        $this->projectRepository      = $projectRepository;
        $this->projectCreatedNotifier = $projectCreatedNotifier;
    }

    /**
     * @throws \Http\Client\Exception
     * @throws \Nexy\Slack\Exception\SlackApiException
     */
    public function __invoke(ProjectCreated $agencyCreated)
    {
        $project = $this->projectRepository->find($agencyCreated->getProjectId());

        if ($project) {
            $this->projectCreatedNotifier->notify($project);
        }
    }
}
