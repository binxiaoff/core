<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Listener\Doctrine\MessageDispatcher;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Agency\Entity\AbstractProjectMember;
use KLS\Syndication\Agency\Message\ProjectMemberCreated;

class ProjectMemberCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(AbstractProjectMember $projectMember)
    {
        $this->messageBus->dispatch(new ProjectMemberCreated($projectMember));
    }
}
