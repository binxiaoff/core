<?php

declare(strict_types=1);

namespace Unilend\Syndication\MessageHandler\Project;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Http\Client\Exception;
use Nexy\Slack\Exception\SlackApiException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Syndication\Message\Project\ProjectCreated;
use Unilend\Syndication\Repository\ProjectRepository;
use Unilend\Syndication\Service\Project\ProjectNotifier;

class ProjectCreatedHandler implements MessageHandlerInterface
{
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var ProjectNotifier */
    private $projectNotifier;

    public function __construct(ProjectRepository $projectRepository, ProjectNotifier $projectNotifier)
    {
        $this->projectRepository = $projectRepository;
        $this->projectNotifier   = $projectNotifier;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     * @throws SlackApiException
     */
    public function __invoke(ProjectCreated $projectCreated)
    {
        $project = $this->projectRepository->find($projectCreated->getProjectId());

        if ($project) {
            $this->projectNotifier->notifyProjectCreated($project);
        }
    }
}
