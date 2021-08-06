<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Entity\MessageDispatcher\ProjectParticipation;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Message\ProjectParticipation\ProjectParticipationStatusUpdated;

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
