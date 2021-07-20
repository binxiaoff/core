<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine\MessageDispatcher;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Unilend\Agency\Entity\AbstractProjectMember;
use Unilend\Agency\Message\ProjectMemberUpdated;
use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

class ProjectMemberUpdatedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(AbstractProjectMember $projectMember, PreUpdateEventArgs $args)
    {
        $this->messageBus->dispatch(new ProjectMemberUpdated($projectMember, \array_keys($args->getEntityChangeSet())));
    }
}
