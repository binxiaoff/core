<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Project;

use Swift_RfcComplianceException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Message\Project\ProjectPublished;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\NotificationManager;

class ProjectPublishedHandler implements MessageHandlerInterface
{
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var NotificationManager */
    private $notificationManager;

    /**
     * @param ProjectRepository   $projectRepository
     * @param NotificationManager $notificationManager
     */
    public function __construct(ProjectRepository $projectRepository, NotificationManager $notificationManager)
    {
        $this->projectRepository   = $projectRepository;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param ProjectPublished $projectRequested
     *
     * @throws Swift_RfcComplianceException
     */
    public function __invoke(ProjectPublished $projectRequested)
    {
        $project = $this->projectRepository->find($projectRequested->getProjectId());
        $this->notificationManager->createProjectPublication($project);
    }
}
