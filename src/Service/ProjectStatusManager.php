<?php

namespace Unilend\Service;

use Doctrine\ORM\{EntityManagerInterface, ORMException, OptimisticLockException};
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{Clients, Project, ProjectStatusHistory};
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Service\User\RealUserFinder;

class ProjectStatusManager
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var SlackManager */
    private $slackManager;
    /** @var ProjectRepository */
    private $projectRepository;
    /** @var RealUserFinder */
    private $realUserFinder;

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     * @param LoggerInterface        $logger
     * @param SlackManager           $slackManager
     * @param ProjectRepository      $projectRepository
     * @param RealUserFinder         $realUserFinder
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        SlackManager $slackManager,
        ProjectRepository $projectRepository,
        RealUserFinder $realUserFinder
    ) {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->translator             = $translator;
        $this->logger                 = $logger;
        $this->slackManager           = $slackManager;
        $this->projectRepository      = $projectRepository;
        $this->realUserFinder         = $realUserFinder;
    }

    /**
     * @param Clients $user
     * @param int     $projectStatus
     * @param Project $project
     */
    public function addProjectStatus(Clients $user, int $projectStatus, $project)
    {
        $projectStatusHistory = (new ProjectStatusHistory())
            ->setStatus($projectStatus)
            ->setAddedByValue($this->realUserFinder)
        ;
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
