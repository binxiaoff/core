<?php

namespace Unilend\Service\Project;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{Clients, Project};

class ProjectCreationManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * ProjectCreationManager constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Project $project
     * @param Clients $submitter
     */
    public function handleCreation(Project $project, Clients $submitter)
    {
        if (false === $this->entityManager->contains($project)) {
            $project->setSubmitterClient($submitter)
                ->setSubmitterCompany($submitter->getCompany())
            ;
            $this->entityManager->persist($project);
        }

        $this->entityManager->flush();
    }
}
