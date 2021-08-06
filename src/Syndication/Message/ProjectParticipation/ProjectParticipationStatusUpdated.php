<?php

declare(strict_types=1);

namespace Unilend\Syndication\Message\ProjectParticipation;

use Unilend\Core\Message\AsyncMessageInterface;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationStatus;

class ProjectParticipationStatusUpdated implements AsyncMessageInterface
{
    private int $projectParticipationId;

    private int $oldStatus;

    private int $newStatus;

    public function __construct(ProjectParticipation $projectParticipation, ProjectParticipationStatus $oldStatus, ProjectParticipationStatus $newStatus)
    {
        $this->projectParticipationId = $projectParticipation->getId();
        $this->oldStatus              = $oldStatus->getStatus();
        $this->newStatus              = $newStatus->getStatus();
    }

    public function getProjectParticipationId(): int
    {
        return $this->projectParticipationId;
    }

    public function getOldStatus(): int
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): int
    {
        return $this->newStatus;
    }
}
