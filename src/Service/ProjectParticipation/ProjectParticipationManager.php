<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\NonUniqueResultException;
use Unilend\Entity\{Clients, Project, ProjectParticipation};
use Unilend\Repository\{ClientsRepository, ProjectParticipationContactRepository, ProjectParticipationRepository};
use Unilend\Service\Staff\StaffManager;

class ProjectParticipationManager
{
    /** @var ClientsRepository */
    private $clientRepository;
    /** @var StaffManager */
    private $staffManager;
    /** @var ProjectParticipationRepository */
    private $projectParticipationRepository;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;

    /**
     * @param ClientsRepository                     $clientRepository
     * @param StaffManager                          $staffManager
     * @param ProjectParticipationRepository        $projectParticipationRepository
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(
        ClientsRepository $clientRepository,
        StaffManager $staffManager,
        ProjectParticipationRepository $projectParticipationRepository,
        ProjectParticipationContactRepository $projectParticipationContactRepository
    ) {
        $this->clientRepository                      = $clientRepository;
        $this->staffManager                          = $staffManager;
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
        $concernedClientsByDefault   = $this->getDefaultConcernedClients($projectParticipation);
        $specifiedClientsAddedByUser = $this->clientRepository->findByProjectParticipation($projectParticipation);

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
     * @param ProjectParticipation $projectParticipation
     *
     * @return Clients[]
     */
    private function getDefaultConcernedClients(ProjectParticipation $projectParticipation): iterable
    {
        $concernedRoles = $this->staffManager->getConcernedRoles($projectParticipation->getProject()->getMarketSegment());

        if ($concernedRoles) {
            return $this->clientRepository->findByStaffRoles($projectParticipation->getCompany(), $concernedRoles);
        }

        return [];
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
