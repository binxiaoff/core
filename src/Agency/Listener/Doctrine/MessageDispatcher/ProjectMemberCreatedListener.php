<?php

declare(strict_types=1);

namespace KLS\Agency\Listener\Doctrine\MessageDispatcher;

use KLS\Agency\Entity\AbstractProjectMember;
use KLS\Agency\Message\ProjectMemberCreated;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

class ProjectMemberCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(AbstractProjectMember $projectMember)
    {
        $this->messageBus->dispatch(new ProjectMemberCreated($projectMember));
    }
}
