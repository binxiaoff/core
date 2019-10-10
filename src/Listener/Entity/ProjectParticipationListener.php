<?php

declare(strict_types=1);

namespace Unilend\Listener\Entity;

use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\ProjectParticipation;
use Unilend\Message\ProjectParticipation\ProjectParticipantInvited;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\User\RealUserFinder;

class ProjectParticipationListener
{
    /** @var ClientsRepository */
    private $clientRepository;

    /** @var MessageBusInterface */
    private $messageBus;
    /** @var RealUserFinder */
    private $realUserFinder;

    /**
     * @param MessageBusInterface $messageBus
     * @param RealUserFinder      $realUserFinder
     * @param ClientsRepository   $clientRepository
     */
    public function __construct(MessageBusInterface $messageBus, RealUserFinder $realUserFinder, ClientsRepository $clientRepository)
    {
        $this->messageBus       = $messageBus;
        $this->clientRepository = $clientRepository;
        $this->realUserFinder   = $realUserFinder;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     */
    public function prePersist(ProjectParticipation $projectParticipation): void
    {
        if ($projectParticipation->isParticipant()) {
            $clients = $this->clientRepository->findDefaultConcernedClients($projectParticipation);
            foreach ($clients as $client) {
                $projectParticipation->addProjectParticipationContact($client, $this->realUserFinder);
            }
        }
    }

    /**
     * @param ProjectParticipation $projectParticipation
     */
    public function postPersist(ProjectParticipation $projectParticipation): void
    {
        if ($projectParticipation->isParticipant()) {
            $this->messageBus->dispatch(new ProjectParticipantInvited($projectParticipation));
        }
    }
}
