<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\ProjectParticipation;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Unilend\Entity\ProjectParticipation;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\ProjectParticipation\ProjectParticipationStatusUpdated;

class ProjectParticipationUpdatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param ProjectParticipation $projectParticipation
     * @param PreUpdateEventArgs   $args
     */
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
