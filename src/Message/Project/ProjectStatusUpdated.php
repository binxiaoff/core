<?php

declare(strict_types=1);

namespace Unilend\Message\Project;

use Unilend\Core\Message\AsyncMessageInterface;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectStatus;

class ProjectStatusUpdated implements AsyncMessageInterface
{
    /** @var int */
    private $projectId;
    /** @var int */
    private $oldStatus;
    /** @var int */
    private $newStatus;

    /**
     * @param Project       $project
     * @param ProjectStatus $oldStatus
     * @param ProjectStatus $newStatus
     */
    public function __construct(Project $project, ProjectStatus $oldStatus, ProjectStatus $newStatus)
    {
        $this->projectId = $project->getId();
        $this->oldStatus = $oldStatus->getStatus();
        $this->newStatus = $newStatus->getStatus();
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
