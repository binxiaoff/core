<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Message\Agency;

use KLS\Core\Message\AsyncMessageInterface;
use KLS\Syndication\Agency\Entity\Project;

class AgencyPublished implements AsyncMessageInterface
{
    private int $projectId;
    private int $newStatus;

    public function __construct(Project $project, int $newStatus)
    {
        $this->projectId = $project->getId();
        $this->newStatus = $newStatus;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getNewStatus(): int
    {
        return $this->newStatus;
    }
}
