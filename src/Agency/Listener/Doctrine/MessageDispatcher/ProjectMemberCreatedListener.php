<?php

declare(strict_types=1);

namespace Unilend\Agency\Listener\Doctrine\MessageDispatcher;

use Unilend\Agency\Entity\AbstractProjectMember;
use Unilend\Agency\Message\ProjectMemberCreated;
use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;

class ProjectMemberCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(AbstractProjectMember $projectMember)
    {
        $this->messageBus->dispatch(new ProjectMemberCreated($projectMember));
    }
}
