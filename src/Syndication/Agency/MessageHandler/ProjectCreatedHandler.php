<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\MessageHandler;

use Http\Client\Exception;
use KLS\Syndication\Agency\Message\ProjectCreated;
use KLS\Syndication\Agency\Notifier\ProjectCreatedNotifier;
use KLS\Syndication\Agency\Repository\ProjectRepository;
use Nexy\Slack\Exception\SlackApiException;
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
     * @throws Exception
     * @throws SlackApiException
     */
    public function __invoke(ProjectCreated $agencyCreated): void
    {
        $project = $this->projectRepository->find($agencyCreated->getProjectId());

        if ($project) {
            $this->projectCreatedNotifier->notify($project);
        }
    }
}
