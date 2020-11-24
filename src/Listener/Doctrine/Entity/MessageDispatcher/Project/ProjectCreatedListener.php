<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Project;

use Unilend\Entity\Project;
use Unilend\Message\Project\ProjectCreated;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

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
