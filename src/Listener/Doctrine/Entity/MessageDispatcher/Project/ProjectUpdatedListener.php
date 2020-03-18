<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Project;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Unilend\Entity\Project;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\Project\ProjectStatusUpdated;

/** TODO Not used for now because there is a problem with doctrine persist call in event
 * TODO Should be reactivated in doctrine_listener.yml when queue are truly asynchronous */
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
