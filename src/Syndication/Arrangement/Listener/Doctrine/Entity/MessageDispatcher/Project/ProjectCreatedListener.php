<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Listener\Doctrine\Entity\MessageDispatcher\Project;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Message\Project\ProjectCreated;

class ProjectCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(Project $project): void
    {
        $this->messageBus->dispatch(new ProjectCreated($project));
    }
}
