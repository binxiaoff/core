<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Entity\MessageDispatcher\Project;

use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Message\Project\ProjectCreated;

class ProjectCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(Project $project): void
    {
        $this->messageBus->dispatch(new ProjectCreated($project));
    }
}
