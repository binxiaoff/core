<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Listener\Doctrine\MessageDispatcher;

use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\PostFlushListener;
use KLS\Syndication\Agency\Entity\AbstractProjectMember;
use KLS\Syndication\Agency\Message\ProjectMemberCreated;

class ProjectMemberCreatedListener
{
    private PostFlushListener $postFlushListener;

    public function __construct(PostFlushListener $postFlushListener)
    {
        $this->postFlushListener = $postFlushListener;
    }

    public function postPersist(AbstractProjectMember $projectMember): void
    {
        $this->postFlushListener->addMessage(new ProjectMemberCreated($projectMember));
    }
}
