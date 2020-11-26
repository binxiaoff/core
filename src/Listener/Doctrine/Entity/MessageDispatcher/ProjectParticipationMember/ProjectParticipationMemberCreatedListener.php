<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\ProjectParticipationMember;

use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Entity\ProjectParticipationMember;
use Unilend\Message\ProjectParticipationMember\ProjectParticipationMemberCreated;

class ProjectParticipationMemberCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param ProjectParticipationMember $projectParticipationMember
     */
    public function postPersist(ProjectParticipationMember $projectParticipationMember): void
    {
        $this->messageBus->dispatch(new ProjectParticipationMemberCreated($projectParticipationMember));
    }
}
