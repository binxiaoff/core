<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\User;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use KLS\Core\Entity\User;
use KLS\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use KLS\Core\Message\User\UserUpdated;

class UserUpdatedListener
{
    use MessageDispatcherTrait;

    private const MONITORED_FIELDS = [
        'lastName',
        'firstName',
        'phone',
        'mobile',
        'email',
        'jobFunction',
    ];

    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        $changeSet = $args->getEntityChangeSet();

        foreach ($changeSet as $updatedField => $newValue) {
            if (false === \in_array($updatedField, self::MONITORED_FIELDS, true)) {
                unset($changeSet[$updatedField]);
            }
        }

        if (false === empty($changeSet)) {
            $this->messageBus->dispatch(new UserUpdated($user, $changeSet));
        }
    }
}
