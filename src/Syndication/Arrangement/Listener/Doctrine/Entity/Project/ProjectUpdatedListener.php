<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Listener\Doctrine\Entity\Project;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use HTMLPurifier;
use KLS\Syndication\Arrangement\Entity\Project;

class ProjectUpdatedListener
{
    /** @var HTMLPurifier */
    private $htmlPurifier;

    public function __construct(HTMLPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }

    public function purify(Project $project, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField(Project::FIELD_DESCRIPTION) && $args->getNewValue(Project::FIELD_DESCRIPTION)) {
            $args->setNewValue(Project::FIELD_DESCRIPTION, $this->htmlPurifier->purify($args->getNewValue(Project::FIELD_DESCRIPTION)));
        }
    }
}
