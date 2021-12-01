<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Listener\Doctrine\Entity\MessageDispatcher\Project;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\PostFlushListener;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Message\Project\ProjectCreated;

class ProjectCreatedListener
{
    private PostFlushListener $postFlushListener;

    public function __construct(PostFlushListener $postFlushListener)
    {
        $this->postFlushListener = $postFlushListener;
    }

    public function postPersist(Project $project): void
    {
        $this->postFlushListener->addMessage(new ProjectCreated($project));
    }
}
