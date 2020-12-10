<?php

declare(strict_types=1);

namespace Unilend\Syndication\Listener\Doctrine\Entity\Project;

use HTMLPurifier;
use Unilend\Syndication\Entity\Project;

class ProjectCreatedListener
{
    /** @var HTMLPurifier */
    private $htmlPurifier;

    /**
     * @param HTMLPurifier $htmlPurifier
     */
    public function __construct(HTMLPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }

    /**
     * @param Project $project
     */
    public function purify(Project $project): void
    {
        if (null === $project->getDescription()) {
            return;
        }

        $project->setDescription($this->htmlPurifier->purify($project->getDescription()));
    }
}
