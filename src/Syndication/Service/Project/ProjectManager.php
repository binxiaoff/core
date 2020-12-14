<?php

declare(strict_types=1);

namespace Unilend\Syndication\Service\Project;

use Unilend\Core\Entity\Staff;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipationMember;
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;
use Unilend\Syndication\Repository\ProjectParticipationRepository;
use Unilend\Syndication\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectManager
{
    /**
     * @var ProjectParticipationManager
     */
    private ProjectParticipationManager $projectParticipationManager;
    /**
     * @var ProjectParticipationRepository
     */
    private ProjectParticipationRepository $projectParticipationRepository;

    /**
     * @param ProjectParticipationManager    $projectParticipationManager
     * @param ProjectParticipationRepository $projectParticipationRepository
     */
    public function __construct(
        ProjectParticipationManager $projectParticipationManager,
        ProjectParticipationRepository $projectParticipationRepository
    ) {
        $this->projectParticipationManager = $projectParticipationManager;
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return bool
     */
    public function isActiveParticipationMember(Project $project, Staff $staff): bool
    {
        return null !== $this->getActiveParticipationMember($project, $staff);
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return ProjectParticipationMember|null
     */
    public function getActiveParticipationMember(Project $project, Staff $staff): ?ProjectParticipationMember
    {
        $projectParticipation = $this->projectParticipationRepository->findOneBy(['project' => $project, 'participant' => $staff->getCompany()]);

        if (null !== $projectParticipation) {
            return null;
        }

        return $this->projectParticipationManager->getActiveMember($projectParticipation, $staff);
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return bool
     */
    public function hasSignedNDA(Project $project, Staff $staff): bool
    {
        $projectParticipation = $this->projectParticipationRepository->findOneBy(['project' => $project, 'participant' => $staff->getCompany()]);

        return $projectParticipation && $this->projectParticipationManager->hasSignedNDA($projectParticipation, $staff);
    }
}
