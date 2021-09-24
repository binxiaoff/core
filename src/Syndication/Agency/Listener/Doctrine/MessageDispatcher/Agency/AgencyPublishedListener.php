<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Listener\Doctrine\MessageDispatcher\Agency;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\Agency\AgencyPublished;

class AgencyPublishedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(Project $project, PreUpdateEventArgs $args): void
    {
        $hasChangedValue = $args->hasChangedField('currentStatus');
        $newValue        = $args->getNewValue('currentStatus');

        if ($hasChangedValue) {
            $this->messageBus->dispatch(
                new AgencyPublished($project, $newValue)
            );
        }
    }
}
