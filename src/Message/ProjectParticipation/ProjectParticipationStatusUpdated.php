<?php

declare(strict_types=1);

namespace Unilend\Message\ProjectParticipation;

use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationStatus;
use Unilend\Message\AsyncMessageInterface;

class ProjectParticipationStatusUpdated implements AsyncMessageInterface
{
    /** @var int */
    private int $projectParticipationId;
    /** @var int */
    private int $oldStatus;
    /** @var int */
    private int $newStatus;

    /**
     * @param ProjectParticipation       $projectParticipation
     * @param ProjectParticipationStatus $oldStatus
     * @param ProjectParticipationStatus $newStatus
     */
    public function __construct(ProjectParticipation $projectParticipation, ProjectParticipationStatus $oldStatus, ProjectParticipationStatus $newStatus)
    {
        $this->projectParticipationId = $projectParticipation->getId();
        $this->oldStatus = $oldStatus->getStatus();
        $this->newStatus = $newStatus->getStatus();
    }

    /**
     * @return int
     */
    public function getProjectParticipationId(): int
    {
        return $this->projectParticipationId;
    }

    /**
     * @return int
     */
    public function getOldStatus(): int
    {
        return $this->oldStatus;
    }

    /**
     * @return int
     */
    public function getNewStatus(): int
    {
        return $this->newStatus;
    }
}
