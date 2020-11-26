<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Staff;

use Unilend\Core\Entity\Staff;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\Staff\StaffCreated;

class StaffCreatedListener
{
    use MessageDispatcherTrait;

    /**
     * @param Staff $staff
     */
    public function postPersist(Staff $staff): void
    {
        $this->messageBus->dispatch(new StaffCreated($staff));
    }
}
