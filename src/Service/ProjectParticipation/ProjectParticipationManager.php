<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\NonUniqueResultException;
use RuntimeException;
use Unilend\Entity\{Clients, Project, ProjectParticipation, Staff};
use Unilend\Repository\ProjectParticipationContactRepository;

class ProjectParticipationManager
{
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;

    /**
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(ProjectParticipationContactRepository $projectParticipationContactRepository)
    {
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
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
        $projectParticipationContact = $this->projectParticipationContactRepository->findByProjectAndStaff($project, $staff);

        return null !== $projectParticipationContact && false === $projectParticipationContact->isArchived();
    }

    /**
     * @param Staff                $staff                we pass staff here to prepare for the migration from client to staff
     * @param ProjectParticipation $projectParticipation
     *
     * @return bool
     */
    public function isParticipationOwner(Staff $staff, ProjectParticipation $projectParticipation): bool
    {
        return null !== $this->projectParticipationContactRepository->findOneBy([
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
        $projectParticipationContact = $this->projectParticipationContactRepository->findByProjectAndStaff($project, $staff);

        if (null === $projectParticipationContact) {
            throw new RuntimeException(sprintf('The staff %s is not a participant of project %s', $staff->getPublicId(), $project->getPublicId()));
        }

        return null !== $projectParticipationContact->getNdaAccepted();
    }
}
