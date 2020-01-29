<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\ProjectStatus;

use Unilend\Entity\ProjectStatus;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\ProjectStatus\ProjectStatusCreated;

class ProjectStatusCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param ProjectStatus $projectStatus
     */
    public function postPersist(ProjectStatus $projectStatus): void
    {
        $this->messageBus->dispatch(new ProjectStatusCreated($projectStatus));
    }
}
