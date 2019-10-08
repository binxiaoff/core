<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\{Clients, Companies, Project, ProjectParticipation};
use Unilend\Message\ProjectParticipation\ProjectParticipantInvited;
use Unilend\Repository\{ClientsRepository, ProjectParticipationContactRepository, ProjectParticipationRepository};
use Unilend\Service\{Staff\StaffManager, User\RealUserFinder};

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
     * @param Project   $project
     * @param Companies $company
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return ProjectParticipation
     */
    public function addParticipantByCompany(Project $project, Companies $company): ProjectParticipation
    {
        $projectParticipation = $project->addParticipant($company, $this->realUserFinder);
        $clients              = $this->getDefaultConcernedClients($projectParticipation);
        foreach ($clients as $client) {
            $projectParticipation->addProjectParticipationContact($client, $this->realUserFinder);
        }

        $this->projectParticipationRepository->save($projectParticipation);

        $this->messageBus->dispatch(new ProjectParticipantInvited($projectParticipation));

        return $projectParticipation;
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
