<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\{Clients, Project, ProjectParticipation};
use Unilend\Repository\{ClientsRepository, ProjectParticipationContactRepository, ProjectParticipationRepository};
use Unilend\Service\User\RealUserFinder;

class ProjectParticipationManager
{
    /** @var ClientsRepository */
    private $clientRepository;
    /** @var ProjectParticipationRepository */
    private $projectParticipationRepository;
    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;
    /** @var RealUserFinder */
    private $realUserFinder;
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param ClientsRepository                     $clientRepository
     * @param ProjectParticipationRepository        $projectParticipationRepository
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     * @param RealUserFinder                        $realUserFinder
     * @param MessageBusInterface                   $messageBus
     */
    public function __construct(
        ClientsRepository $clientRepository,
        ProjectParticipationRepository $projectParticipationRepository,
        ProjectParticipationContactRepository $projectParticipationContactRepository,
        RealUserFinder $realUserFinder,
        MessageBusInterface $messageBus
    ) {
        $this->clientRepository                      = $clientRepository;
        $this->projectParticipationRepository        = $projectParticipationRepository;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
        $this->realUserFinder                        = $realUserFinder;
        $this->messageBus                            = $messageBus;
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
