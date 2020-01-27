<?php

declare(strict_types=1);

namespace Unilend\Message\Project;

use Unilend\Entity\Project;

class ProjectStatusUpdated
{
    /** @var int */
    private $projectId;
    /** @var int */
    private $oldStatus;
    /** @var int */
    private $newStatus;

    /**
     * @param Project $project
     * @param int     $oldStatus
     * @param int     $newStatus
     */
    public function __construct(Project $project, int $oldStatus, int $newStatus)
    {
        $this->projectId = $project->getId();
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
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
