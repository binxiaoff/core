<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Entity\MessageDispatcher\ProjectParticipationMember;

use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Syndication\Entity\ProjectParticipationMember;
use Unilend\Syndication\Message\ProjectParticipationMember\ProjectParticipationMemberCreated;

class ProjectParticipationMemberCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(ProjectParticipationMember $projectParticipationMember): void
    {
        $this->messageBus->dispatch(new ProjectParticipationMemberCreated($projectParticipationMember));
    }
}
