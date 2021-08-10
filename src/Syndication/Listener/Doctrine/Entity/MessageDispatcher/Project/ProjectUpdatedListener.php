<?php

declare(strict_types=1);

namespace KLS\Syndication\Listener\Doctrine\Entity\MessageDispatcher\Project;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Entity\Project;
use KLS\Syndication\Message\Project\ProjectStatusUpdated;

class ProjectUpdatedListener
{
    use MessageDispatcherTrait;

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
