<?php

declare(strict_types=1);

namespace KLS\Syndication\Service\Project;

use KLS\Core\Entity\Staff;
use KLS\Syndication\Entity\Project;
use KLS\Syndication\Entity\ProjectParticipationMember;
use KLS\Syndication\Repository\ProjectParticipationRepository;
use KLS\Syndication\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectManager
{
    private ProjectParticipationManager $projectParticipationManager;

    private ProjectParticipationRepository $projectParticipationRepository;

    public function __construct(
        ProjectParticipationManager $projectParticipationManager,
        ProjectParticipationRepository $projectParticipationRepository
    ) {
        $this->projectParticipationManager    = $projectParticipationManager;
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    public function isActiveParticipationMember(Project $project, Staff $staff): bool
    {
        return null !== $this->getActiveParticipationMember($project, $staff);
    }

    public function getActiveParticipationMember(Project $project, Staff $staff): ?ProjectParticipationMember
    {
        $projectParticipation = $this->projectParticipationRepository->findOneBy(['project' => $project, 'participant' => $staff->getCompany()]);

        if (null !== $projectParticipation) {
            return null;
        }

        return $this->projectParticipationManager->getActiveMember($projectParticipation, $staff);
    }

    public function hasSignedNDA(Project $project, Staff $staff): bool
    {
        $projectParticipation = $this->projectParticipationRepository->findOneBy(['project' => $project, 'participant' => $staff->getCompany()]);

        return $projectParticipation && $this->projectParticipationManager->hasSignedNDA($projectParticipation, $staff);
    }
}
