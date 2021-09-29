<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Listener\Doctrine\MessageDispatcher;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Message\ProjectCreated;

class ProjectCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(Project $project): void
    {
        $this->messageBus->dispatch(new ProjectCreated($project));
    }
}
