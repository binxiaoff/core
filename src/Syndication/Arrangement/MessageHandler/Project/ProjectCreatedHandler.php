<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\MessageHandler\Project;

use Http\Client\Exception;
use KLS\Syndication\Arrangement\Message\Project\ProjectCreated;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Service\Project\SlackNotifier\ProjectCreateNotifier;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProjectCreatedHandler implements MessageHandlerInterface
{
    private ProjectRepository $projectRepository;
    private ProjectCreateNotifier $projectCreateNotifier;

    public function __construct(ProjectRepository $projectRepository, ProjectCreateNotifier $projectCreateNotifier)
    {
        $this->projectRepository     = $projectRepository;
        $this->projectCreateNotifier = $projectCreateNotifier;
    }

    /**
     * @throws Exception
     * @throws SlackApiException
     */
    public function __invoke(ProjectCreated $projectCreated): void
    {
        $project = $this->projectRepository->find($projectCreated->getProjectId());

        if ($project) {
            $this->projectCreateNotifier->notify($project);
        }
    }
}
