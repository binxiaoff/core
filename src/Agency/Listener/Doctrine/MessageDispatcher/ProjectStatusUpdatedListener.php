<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine\MessageDispatcher;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Message\ProjectStatusUpdated;
use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

class ProjectStatusUpdatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param Project $projectParticipation
     */
    public function preUpdate(Project $project, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('currentStatus')) {
            $this->messageBus->dispatch(
                new ProjectStatusUpdated($project->getId(), $args->getOldValue('currentStatus'), $args->getNewValue('currentStatus'))
            );
        }
    }
}
