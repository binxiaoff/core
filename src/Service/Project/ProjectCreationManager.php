<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{Clients, Project, ProjectStatusHistory, ProjectsStatus};

class ProjectCreationManager
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * ProjectCreationManager constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param Project $project
     * @param Clients $submitter
     */
    public function handleBlamableCreation(Project $project, Clients $submitter)
    {
        $entityManager = $this->managerRegistry->getManagerForClass(get_class($project));

        $projectStatusHistory = (new ProjectStatusHistory())
            ->setStatus(ProjectsStatus::STATUS_REQUESTED)
            ->setAddedBy($submitter)
        ;

        $project->setSubmitterClient($submitter)
            ->setSubmitterCompany($submitter->getCompany())
            ->addProjectStatusHistory($projectStatusHistory)
        ;

        $project->setLastProjectStatusHistory($projectStatusHistory);

        $entityManager->persist($project);
        $entityManager->flush();
    }
}
