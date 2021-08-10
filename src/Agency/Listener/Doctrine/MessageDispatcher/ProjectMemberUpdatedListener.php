<?php

declare(strict_types=1);

namespace KLS\Agency\Listener\Doctrine\MessageDispatcher;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Agency\Entity\AbstractProjectMember;
use KLS\Agency\Message\ProjectMemberUpdated;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

class ProjectMemberUpdatedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(AbstractProjectMember $projectMember, PreUpdateEventArgs $args)
    {
        $this->messageBus->dispatch(new ProjectMemberUpdated($projectMember, \array_keys($args->getEntityChangeSet())));
    }
}
