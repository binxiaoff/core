<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Listener\Doctrine\MessageDispatcher;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Agency\Entity\AbstractProjectMember;
use KLS\Syndication\Agency\Message\ProjectMemberUpdated;

class ProjectMemberUpdatedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(AbstractProjectMember $projectMember, PreUpdateEventArgs $args)
    {
        $this->messageBus->dispatch(new ProjectMemberUpdated($projectMember, \array_keys($args->getEntityChangeSet())));
    }
}
