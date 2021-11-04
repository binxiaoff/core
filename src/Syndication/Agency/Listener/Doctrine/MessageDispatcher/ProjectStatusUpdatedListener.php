<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Listener\Doctrine\MessageDispatcher;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\ProjectStatusUpdated;

class ProjectStatusUpdatedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(Project $project, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('currentStatus')) {
            $this->messageBus->dispatch(
                new ProjectStatusUpdated(
                    $project->getId(),
                    $args->getOldValue('currentStatus'),
                    $args->getNewValue('currentStatus')
                )
            );
        }
    }
}
