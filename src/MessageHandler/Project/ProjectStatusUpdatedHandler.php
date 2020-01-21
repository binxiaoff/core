<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Project;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\ProjectStatus;
use Unilend\Message\Project\ProjectStatusUpdated;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Project\ProjectNotifier;

class ProjectStatusUpdatedHandler implements MessageHandlerInterface
{
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var ProjectNotifier */
    private $projectNotifier;

    /**
     * @param ProjectRepository $projectRepository
     * @param ProjectNotifier   $projectNotifier
     */
    public function __construct(ProjectRepository $projectRepository, ProjectNotifier $projectNotifier)
    {
        $this->projectRepository = $projectRepository;
        $this->projectNotifier   = $projectNotifier;
    }

    /**
     * @param ProjectStatusUpdated $projectStatusUpdated
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(ProjectStatusUpdated $projectStatusUpdated)
    {
        $project = $this->projectRepository->find($projectStatusUpdated->getProjectId());
        if (
            $project
            && ProjectStatus::STATUS_PUBLISHED > $projectStatusUpdated->getOldStatus()
            && ProjectStatus::STATUS_PUBLISHED <= $projectStatusUpdated->getNewStatus()
        ) {
            $this->projectNotifier->notifyProjectPublished($project);
        }
    }
}
