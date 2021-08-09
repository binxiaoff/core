<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\Staff;

use KLS\Core\Entity\Staff;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Core\Message\Staff\StaffCreated;

class StaffCreatedListener
{
    use MessageDispatcherTrait;

    public function postPersist(Staff $staff): void
    {
        $this->messageBus->dispatch(new StaffCreated($staff));
    }
}
