<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\ProjectParticipationContact;

use Unilend\Entity\ProjectParticipationContact;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\ProjectParticipationContact\ProjectParticipationContactCreated;

class ProjectParticipationContactCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param ProjectParticipationContact $projectParticipationContact
     */
    public function postPersist(ProjectParticipationContact $projectParticipationContact): void
    {
        $this->messageBus->dispatch(new ProjectParticipationContactCreated($projectParticipationContact));
    }
}
