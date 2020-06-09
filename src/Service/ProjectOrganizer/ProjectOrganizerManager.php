<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectOrganizer;

use Unilend\Entity\{Project, ProjectOrganizer, Staff};
use Unilend\Repository\ProjectOrganizerRepository;

class ProjectOrganizerManager
{
    /** @var ProjectOrganizerRepository */
    private $projectOrganizerRepository;

    /**
     * @param ProjectOrganizerRepository $projectOrganizerRepository
     */
    public function __construct(ProjectOrganizerRepository $projectOrganizerRepository)
    {
        $this->projectOrganizerRepository = $projectOrganizerRepository;
    }

    /**
     * @param Staff   $staff
     * @param Project $project
     *
     * @return bool
     */
    public function isOrganizer(Staff $staff, Project $project): bool
    {
        $projectOrganizer = $this->projectOrganizerRepository->findOneBy(['project' => $project, 'company' => $staff->getCompany()]);

        return $projectOrganizer && $projectOrganizer->isArranger();
    }
}
