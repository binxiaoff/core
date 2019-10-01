<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Unilend\Entity\{Clients, ProjectParticipation};
use Unilend\Repository\ClientsRepository;
use Unilend\Service\Staff\StaffManager;

class ProjectParticipationManager
{
    /** @var ClientsRepository */
    private $clientRepository;
    /** @var StaffManager */
    private $staffManager;

    /**
     * @param ClientsRepository $clientRepository
     * @param StaffManager      $staffManager
     */
    public function __construct(ClientsRepository $clientRepository, StaffManager $staffManager)
    {
        $this->clientRepository = $clientRepository;
        $this->staffManager     = $staffManager;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return Clients[]
     */
    public function getConcernedClients(ProjectParticipation $projectParticipation): iterable
    {
        $concernedClientsByDefault     = $this->getDefaultConcernedClients($projectParticipation);
        $exceptionalClientsAddedByUser = $this->clientRepository->findByProjectParticipation($projectParticipation);

        return array_unique(array_merge($concernedClientsByDefault, $exceptionalClientsAddedByUser));
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
}
