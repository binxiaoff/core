<?php

declare(strict_types=1);

namespace Unilend\Service;

namespace Unilend\Service\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{Project, ProjectStatus};
use Unilend\Repository\ProjectRepository;
use Unilend\Service\User\RealUserFinder;

class ProjectStatusManager
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var RealUserFinder */
    private $realUserFinder;

    /**
     * @param LoggerInterface   $logger
     * @param ProjectRepository $projectRepository
     * @param RealUserFinder    $realUserFinder
     */
    public function __construct(
        LoggerInterface $logger,
        ProjectRepository $projectRepository,
        RealUserFinder $realUserFinder
    ) {
        $this->logger            = $logger;
        $this->projectRepository = $projectRepository;
        $this->realUserFinder    = $realUserFinder;
    }

    /**
     * @param int     $projectStatus
     * @param Project $project
     *
     * @throws Exception
     */
    public function addProjectStatus(int $projectStatus, $project): void
    {
        $projectStatusHistory = (new ProjectStatus($project, $projectStatus))->setAddedByValue($this->realUserFinder);

        $project->setProjectStatusHistory($projectStatusHistory);

        try {
            $this->projectRepository->save($project);
        } catch (OptimisticLockException | ORMException $exception) {
            $this->logger->critical(sprintf('An exception occurred while updating project status for project %s. Message: %s', $project->getId(), $exception->getMessage()), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }
}
