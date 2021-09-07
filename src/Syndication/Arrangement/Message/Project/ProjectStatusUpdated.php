<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Message\Project;

use KLS\Core\Message\AsyncMessageInterface;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;

class ProjectStatusUpdated implements AsyncMessageInterface
{
    /** @var int */
    private $projectId;
    /** @var int */
    private $oldStatus;
    /** @var int */
    private $newStatus;

    public function __construct(Project $project, ProjectStatus $oldStatus, ProjectStatus $newStatus)
    {
        $this->projectId = $project->getId();
        $this->oldStatus = $oldStatus->getStatus();
        $this->newStatus = $newStatus->getStatus();
    }

    public function getProjectId(): int
    {
        return $this->projectId;
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
