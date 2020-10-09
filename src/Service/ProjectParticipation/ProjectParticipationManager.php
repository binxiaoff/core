<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\NonUniqueResultException;
use RuntimeException;
use Unilend\Entity\{Project, ProjectParticipation, Staff};
use Unilend\Repository\ProjectParticipationMemberRepository;

class ProjectParticipationManager
{
    /** @var ProjectParticipationMemberRepository */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    /**
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     */
    public function __construct(ProjectParticipationMemberRepository $projectParticipationMemberRepository)
    {
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
    }

    /**
     * TODO Should be moved to ProjectManager
     *
     * @param Staff   $staff
     * @param Project $project
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function isParticipant(Staff $staff, Project $project): bool
    {
        $projectParticipationMember = $this->projectParticipationMemberRepository->findByProjectAndStaff($project, $staff);

        return null !== $projectParticipationMember && false === $projectParticipationMember->isArchived();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return bool
     */
    public function isParticipationMember(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        return null !== $this->projectParticipationMemberRepository->findOneBy([
            'projectParticipation' => $projectParticipation,
            'staff'                => $staff,
            'archived'             => null,
        ]);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return bool
     */
    public function isParticipationOwner(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        $participant = $projectParticipation->getParticipant();

        // As an arranger, the user doesn't need the participation module to edit the following participation.
        if ($this->isParticipationArranger($projectParticipation, $staff)) {
            // The one of a prospect in the same company group.
            if (($participant->isProspect() || $participant->hasRefused()) && $participant->isSameGroup($staff->getCompany())) {
                return true;
            }
            // Or the one of arranger's own (we don't check if the user is a participation member for the arranger's participation)
            if ($participant === $staff->getCompany()) {
                return true;
            }
        }

        return $this->isParticipationMember($projectParticipation, $staff);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return bool
     */
    public function isParticipationArranger(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        return $projectParticipation->getProject()->getSubmitterCompany() === $staff->getCompany();
    }

    /**
     * @param Staff   $staff
     * @param Project $project
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function isNdaAccepted(Staff $staff, Project $project): bool
    {
        $projectParticipationMember = $this->projectParticipationMemberRepository->findByProjectAndStaff($project, $staff);

        if (null === $projectParticipationMember) {
            throw new RuntimeException(sprintf('The staff %s is not a participant of project %s', $staff->getPublicId(), $project->getPublicId()));
        }

        return null !== $projectParticipationMember->getNdaAccepted();
    }
}
