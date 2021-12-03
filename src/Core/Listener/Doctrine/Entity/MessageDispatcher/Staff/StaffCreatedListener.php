<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\Staff;

use KLS\Core\Entity\Staff;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\PostFlushListener;
use KLS\Core\Message\Staff\StaffCreated;

class StaffCreatedListener
{
    private PostFlushListener $postFlushListener;

    public function __construct(PostFlushListener $postFlushListener)
    {
        $this->postFlushListener = $postFlushListener;
    }

    public function postPersist(Staff $staff): void
    {
        $this->postFlushListener->addMessage(new StaffCreated($staff));
    }
}
