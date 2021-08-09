<?php

declare(strict_types=1);

namespace KLS\Agency\Listener\Doctrine\MessageDispatcher;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Agency\Entity\Project;
use KLS\Agency\Message\ProjectStatusUpdated;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

class ProjectStatusUpdatedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(Project $project, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('currentStatus')) {
            $this->messageBus->dispatch(
                new ProjectStatusUpdated($project->getId(), $args->getOldValue('currentStatus'), $args->getNewValue('currentStatus'))
            );
        }
    }
}
