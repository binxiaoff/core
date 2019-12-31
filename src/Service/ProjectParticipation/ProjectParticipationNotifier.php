<?php

declare(strict_types=1);

namespace Unilend\Service\ProjectParticipation;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{ProjectParticipation, ProjectStatus};
use Unilend\Service\Client\ClientNotifier;

class ProjectParticipationNotifier
{
    /** @var ClientNotifier */
    private $clientNotifier;
    /** @var ProjectParticipationManager */
    private $projectParticipationManager;

    /**
     * @param ProjectParticipationManager $projectParticipationManager
     * @param ClientNotifier              $clientNotifier
     */
    public function __construct(ProjectParticipationManager $projectParticipationManager, ClientNotifier $clientNotifier)
    {
        $this->projectParticipationManager = $projectParticipationManager;
        $this->clientNotifier              = $clientNotifier;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function notifyParticipantInvited(ProjectParticipation $projectParticipation): int
    {
        $sent = 0;
        if (ProjectStatus::STATUS_PUBLISHED === $projectParticipation->getProject()->getCurrentStatus()->getStatus()) {
            foreach ($projectParticipation->getProjectParticipationContacts() as $contact) {
                $inviter = $this->projectParticipationManager->getInviter($projectParticipation, $contact->getClient());
                $sent += $this->clientNotifier->notifyInvited($inviter, $contact->getClient(), $projectParticipation->getProject());
            }
        }

        return $sent;
    }
}
