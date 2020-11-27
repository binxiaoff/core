<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Entity\MessageDispatcher\Project;

use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\Project\ProjectCreated;
use Unilend\Syndication\Entity\Project;

class ProjectCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param Project $project
     */
    public function postPersist(Project $project): void
    {
        $this->messageBus->dispatch(new ProjectCreated($project));
    }
}
