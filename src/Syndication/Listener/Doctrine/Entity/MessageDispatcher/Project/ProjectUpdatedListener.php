<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Entity\MessageDispatcher\Project;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\Project\ProjectStatusUpdated;
use Unilend\Syndication\Entity\Project;

class ProjectUpdatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param Project            $project
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Project $project, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField(Project::FIELD_CURRENT_STATUS)) {
            $this->messageBus->dispatch(new ProjectStatusUpdated(
                $project,
                $args->getOldValue(Project::FIELD_CURRENT_STATUS),
                $args->getNewValue(Project::FIELD_CURRENT_STATUS)
            ));
        }
    }
}
