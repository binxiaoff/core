<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\NonUniqueResultException;
use Unilend\Entity\{Clients, Project, ProjectParticipation};
use Unilend\Repository\{ClientsRepository, ProjectParticipationContactRepository, ProjectParticipationRepository};

class ProjectParticipationManager
{
    /** @var ClientsRepository */
    private $clientRepository;
    /** @var ProjectParticipationRepository */
    private $projectParticipationRepository;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;

    /**
     * @param ClientsRepository                     $clientRepository
     * @param ProjectParticipationRepository        $projectParticipationRepository
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(
        ClientsRepository $clientRepository,
        ProjectParticipationRepository $projectParticipationRepository,
        ProjectParticipationContactRepository $projectParticipationContactRepository
    ) {
        $this->clientRepository                      = $clientRepository;
        $this->projectParticipationRepository        = $projectParticipationRepository;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return Clients[]
     */
    public function getConcernedClients(ProjectParticipation $projectParticipation): iterable
    {
        $concernedClientsByDefault   = $this->clientRepository->findDefaultConcernedClients($projectParticipation);
        $specifiedClientsAddedByUser = $this->clientRepository->findByProjectParticipationContact($projectParticipation);

        return array_unique(array_merge($concernedClientsByDefault, $specifiedClientsAddedByUser));
    }

    /**
     * @param Clients $client
     * @param Project $project
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function isConcernedClient(Clients $client, Project $project): bool
    {
        return $this->isDefaultConcernedClients($client, $project) || $this->isSpecifiedClientAddedByUser($client, $project);
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
            return $projectParticipationContact->getAddedBy();
        }

        return $projectParticipation->getAddedBy();
    }

    /**
     * @param Clients $client
     * @param Project $project
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    private function isDefaultConcernedClients(Clients $client, Project $project): bool
    {
        return null !== $this->projectParticipationRepository->findByStaff($project, $client->getStaff());
    }

    /**
     * @param Clients $client
     * @param Project $project
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    private function isSpecifiedClientAddedByUser(Clients $client, Project $project): bool
    {
        return null !== $this->projectParticipationContactRepository->findByProjectAndClient($project, $client);
    }
}
