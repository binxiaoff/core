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
        return null !== $this->projectParticipationContactRepository->findByProjectAndStaff($project, $staff);
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
            'client'               => $staff->getClient(),
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
    public function isConfidentialityAccepted(Staff $staff, Project $project): bool
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->findByProjectAndStaff($project, $staff);

        if (null === $projectParticipationContact) {
            throw new RuntimeException(sprintf('The staff %s is not a participant of project %s', $staff->getPublicId(), $project->getPublicId()));
        }

        return null !== $projectParticipationContact->getConfidentialityAccepted();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $client
     *
     * @return bool
     */
    public function hasEditRight(ProjectParticipation $projectParticipation, Clients $client): bool
    {
        return $projectParticipation->getProject()->getSubmitterCompany() === $client->getCompany()
                || (
                    $this->isParticipationOwner($client->getCurrentStaff(), $projectParticipation)
                    && !in_array(
                        $projectParticipation->getCommitteeStatus(),
                        [ProjectParticipation::COMMITTEE_STATUS_ACCEPTED, ProjectParticipation::COMMITTEE_STATUS_REJECTED],
                        true
                    )
                );
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $client
     *
     * @return bool
     */
    public function canEdit(ProjectParticipation $projectParticipation, Clients $client): bool
    {
        return $projectParticipation->isActive() && $this->hasEditRight($projectParticipation, $client);
    }
}
