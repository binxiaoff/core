<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Message;

use KLS\Core\Message\AsyncMessageInterface;
use KLS\Syndication\Agency\Entity\Project;

class ProjectCreated implements AsyncMessageInterface
{
    private int $projectId;

    public function __construct(Project $project)
    {
        $this->projectId = $project->getId();
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }
}
