<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Swift_RfcComplianceException;
use Unilend\Entity\{ProjectParticipation, ProjectStatus};
use Unilend\Repository\ClientsRepository;
use Unilend\Service\Client\ClientNotifier;

class ProjectParticipationNotifier
{
    /** @var ClientNotifier */
    private $clientNotifier;
    /** @var ProjectParticipationManager */
    private $projectParticipationManager;
    /** @var ClientsRepository */
    private $clientRepository;

    /**
     * @param ProjectParticipationManager $projectParticipationManager
     * @param ClientNotifier              $clientNotifier
     * @param ClientsRepository           $clientRepository
     */
    public function __construct(ProjectParticipationManager $projectParticipationManager, ClientNotifier $clientNotifier, ClientsRepository $clientRepository)
    {
        $this->projectParticipationManager = $projectParticipationManager;
        $this->clientNotifier              = $clientNotifier;
        $this->clientRepository            = $clientRepository;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     *
     * @return int
     */
    public function notifyParticipantInvited(ProjectParticipation $projectParticipation): int
    {
        $sent = 0;
        if (ProjectStatus::STATUS_PUBLISHED === $projectParticipation->getProject()->getCurrentStatus()->getStatus()) {
            $concernedInvitees = $this->clientRepository->findByProjectParticipation($projectParticipation);
            foreach ($concernedInvitees as $invitee) {
                $inviter = $this->projectParticipationManager->getInviter($projectParticipation, $invitee);
                $sent += $this->clientNotifier->notifyInvited($inviter, $invitee, $projectParticipation->getProject());
            }
        }

        return $sent;
    }
}
