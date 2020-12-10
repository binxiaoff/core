<?php

declare(strict_types=1);

namespace Unilend\Syndication\Message\ProjectParticipation;

use Unilend\Core\Message\AsyncMessageInterface;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationStatus;

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
