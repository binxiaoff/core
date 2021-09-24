<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\MessageHandler\Agency;

use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\Agency\AgencyPublished;
use KLS\Syndication\Agency\Repository\ProjectRepository;
use KLS\Syndication\Agency\Service\Notifier\Agency\AgencyPublishedNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class AgencyPublishedHandler implements MessageHandlerInterface
{
    private ProjectRepository $projectRepository;
    private AgencyPublishedNotifier $agencyPublishedNotifier;

    public function __construct(ProjectRepository $projectRepository, AgencyPublishedNotifier $agencyPublishedNotifier)
    {
        $this->projectRepository       = $projectRepository;
        $this->agencyPublishedNotifier = $agencyPublishedNotifier;
    }

    /**
     * @throws \Http\Client\Exception
     * @throws \Nexy\Slack\Exception\SlackApiException
     */
    public function __invoke(AgencyPublished $agencyPublished)
    {
        $project = $this->projectRepository->find($agencyPublished->getProjectId());

        if ($project && Project::STATUS_PUBLISHED === $agencyPublished->getNewStatus()) {
            $this->agencyPublishedNotifier->notify($project);
        }
    }
}
