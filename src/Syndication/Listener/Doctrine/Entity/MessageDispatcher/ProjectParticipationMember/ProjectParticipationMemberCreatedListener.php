<?php

declare(strict_types=1);

namespace KLS\Syndication\Listener\Doctrine\Entity\MessageDispatcher\ProjectParticipationMember;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Entity\ProjectParticipationMember;
use KLS\Syndication\Message\ProjectParticipationMember\ProjectParticipationMemberCreated;

class ProjectParticipationMemberCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(ProjectParticipationMember $projectParticipationMember): void
    {
        $this->messageBus->dispatch(new ProjectParticipationMemberCreated($projectParticipationMember));
    }
}
