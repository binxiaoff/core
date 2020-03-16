<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\NonUniqueResultException;
use RuntimeException;
use Unilend\Entity\{Clients, Project, ProjectParticipation};
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
     * @param Clients $client
     * @param Project $project
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function isParticipant(Clients $client, Project $project): bool
    {
        return null !== $this->projectParticipationContactRepository->findByProjectAndClient($project, $client);
    }

    /**
     * @param Clients $client
     * @param Project $project
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function isConfidentialityAccepted(Clients $client, Project $project): bool
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->findByProjectAndClient($project, $client);

        if (null === $projectParticipationContact) {
            throw new RuntimeException(sprintf('The client %s is not a participant of project %s', $client->getPublicId(), $project->getHash()));
        }

        return null !== $projectParticipationContact->getConfidentialityAccepted();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $invitee
     *
     * @return Clients
     */
    public function getInviter(ProjectParticipation $projectParticipation, Clients $invitee): Clients
    {
        $projectParticipationContact = $this->projectParticipationContactRepository->findOneBy(['client' => $invitee, 'projectParticipation' => $projectParticipation]);
        if ($projectParticipationContact) {
            return $projectParticipationContact->getAddedBy()->getClient();
        }

        return $projectParticipation->getAddedBy()->getClient();
    }
}
