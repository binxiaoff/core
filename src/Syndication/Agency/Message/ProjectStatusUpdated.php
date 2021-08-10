<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Message;

use KLS\Core\Message\AsyncMessageInterface;

class ProjectStatusUpdated implements AsyncMessageInterface
{
    private int $projectId;
    private int $previousStatus;
    private int $nextStatus;

    public function __construct(int $projectId, int $previousStatus, int $nextStatus)
    {
        $this->projectId      = $projectId;
        $this->previousStatus = $previousStatus;
        $this->nextStatus     = $nextStatus;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getPreviousStatus(): int
    {
        return $this->previousStatus;
    }

    public function getNextStatus(): int
    {
        return $this->nextStatus;
    }
}
