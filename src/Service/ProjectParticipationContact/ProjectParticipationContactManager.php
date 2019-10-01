<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipationContact;

use Unilend\Entity\{Clients, ProjectParticipation};
use Unilend\Repository\ProjectParticipationContactRepository;

class ProjectParticipationContactManager
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
}
