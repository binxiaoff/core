<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Project;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Unilend\Entity\Project;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\Project\ProjectStatusUpdated;

class ProjectUpdatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param Project            $project
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Project $project, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField(Project::MONITORED_FIELD_CURRENT_STATUS)) {
            $this->messageBus->dispatch(new ProjectStatusUpdated(
                $project,
                $args->getOldValue(Project::MONITORED_FIELD_CURRENT_STATUS),
                $args->getNewValue(Project::MONITORED_FIELD_CURRENT_STATUS)
            ));
        }
    }
}
