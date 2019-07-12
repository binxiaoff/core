<?php

declare(strict_types=1);

namespace Unilend\Message\Project;

class ProjectRequested
{
    /** @var int */
    private $projectId;

    /**
     * @param int $projectId
     */
    public function __construct(int $projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }
}
