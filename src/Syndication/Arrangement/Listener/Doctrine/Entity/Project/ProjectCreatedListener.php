<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Listener\Doctrine\Entity\Project;

use HTMLPurifier;
use KLS\Syndication\Arrangement\Entity\Project;

class ProjectCreatedListener
{
    /** @var HTMLPurifier */
    private $htmlPurifier;

    public function __construct(HTMLPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }

    public function purify(Project $project): void
    {
        if (null === $project->getDescription()) {
            return;
        }

        $project->setDescription($this->htmlPurifier->purify($project->getDescription()));
    }
}
