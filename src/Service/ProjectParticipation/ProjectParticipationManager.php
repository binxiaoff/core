<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Unilend\Entity\{ProjectParticipation, Staff};
use Unilend\Repository\ProjectParticipationMemberRepository;
use Unilend\Service\Project\ProjectManager;

class ProjectParticipationManager
{
    /** @var ProjectParticipationMemberRepository */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    /** @var ProjectManager */
    private ProjectManager $projectManager;

    /**
     * @param ProjectManager                       $projectManager
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     */
    public function __construct(
        ProjectManager $projectManager,
        ProjectParticipationMemberRepository $projectParticipationMemberRepository
    ) {
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
        $this->projectManager = $projectManager;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return bool
     */
    public function isMember(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        return null !== $this->projectParticipationMemberRepository->findOneBy([
            'projectParticipation' => $projectParticipation,
            'staff'                => $staff,
            'archived'             => null,
        ]);
    }

    /**
     * Returns true if for given projectParticipation,
     *
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return bool
     */
    public function isOwner(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        $participant = $projectParticipation->getParticipant();

        // As an arranger, the user doesn't need the participation module to edit the following participation.
        if ($this->isArranger($projectParticipation, $staff)) {
            // The one of a prospect in the same company group.
            if (($participant->isProspect() || $participant->hasRefused()) && $participant->isSameGroup($staff->getCompany())) {
                return true;
            }
            // Or the one of arranger's own (we don't check if the user is a participation member for the arranger's participation)
            if ($participant === $staff->getCompany()) {
                return true;
            }
        }

        return $this->isMember($projectParticipation, $staff);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return bool
     */
    public function isArranger(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        return $this->projectManager->isArranger($projectParticipation->getProject(), $staff);
    }
}
