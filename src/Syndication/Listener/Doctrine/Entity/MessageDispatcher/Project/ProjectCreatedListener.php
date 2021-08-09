<?php

declare(strict_types=1);

namespace KLS\Syndication\Listener\Doctrine\Entity\MessageDispatcher\Project;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Entity\Project;
use KLS\Syndication\Message\Project\ProjectCreated;

class ProjectCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(Project $project): void
    {
        $this->messageBus->dispatch(new ProjectCreated($project));
    }
}
