<?php

declare(strict_types=1);

namespace KLS\Syndication\Listener\Doctrine\Entity\MessageDispatcher\ProjectParticipation;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Entity\ProjectParticipation;
use KLS\Syndication\Message\ProjectParticipation\ProjectParticipationStatusUpdated;

class ProjectParticipationUpdatedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(ProjectParticipation $projectParticipation, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('currentStatus')) {
            $this->messageBus->dispatch(new ProjectParticipationStatusUpdated(
                $projectParticipation,
                $args->getOldValue('currentStatus'),
                $args->getNewValue('currentStatus')
            ));
        }
    }
}
