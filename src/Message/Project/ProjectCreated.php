<?php

declare(strict_types=1);

namespace Unilend\Message\Project;

use Unilend\Entity\Project;
use Unilend\Message\AsyncMessageInterface;

class ProjectCreated implements AsyncMessageInterface
{
    private $projectId;

    /**
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->projectId = $project->getId();
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }
}
