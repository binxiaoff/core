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
     * @param Staff                $staff                we pass staff here to prepare for the migration from client to staff
     * @param ProjectParticipation $projectParticipation
     *
     * @return bool
     */
    public function isParticipationOwner(Staff $staff, ProjectParticipation $projectParticipation): bool
    {
        return null !== $this->projectParticipationMemberRepository->findOneBy([
            'projectParticipation' => $projectParticipation,
            'staff'                => $staff,
            'archived'             => null,
        ]);
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
