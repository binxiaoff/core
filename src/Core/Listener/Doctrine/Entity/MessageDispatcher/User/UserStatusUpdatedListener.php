<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\User;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Entity\User;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Core\Message\Message\User\UserCreated;

class UserStatusUpdatedListener
{
    use MessageDispatcherTrait;

    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('currentStatus')) {
            $this->messageBus->dispatch(
                new UserCreated(
                    $user,
                    $args->getOldValue('currentStatus')->getStatus(),
                    $args->getNewValue('currentStatus')->getStatus()
                )
            );
        }
    }
}
