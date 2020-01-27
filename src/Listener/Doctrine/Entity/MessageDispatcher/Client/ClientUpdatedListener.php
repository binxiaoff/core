<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity\MessageDispatcher\Client;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use libphonenumber\PhoneNumber;
use Unilend\Entity\Clients;
use Unilend\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Message\Client\ClientUpdated;

class ClientUpdatedListener
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

    /**
     * @param Clients            $client
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Clients $client, PreUpdateEventArgs $args): void
    {
        $changeSet = $args->getEntityChangeSet();

        foreach ($changeSet as $updatedField => $newValue) {
            if (
                false === in_array($updatedField, self::MONITORED_FIELDS, true)
                || ($args->getOldValue($updatedField) instanceof PhoneNumber
                    && $args->getNewValue($updatedField) instanceof PhoneNumber
                    && $args->getOldValue($updatedField)->equals($args->getNewValue($updatedField)))
            ) {
                unset($changeSet[$updatedField]);
            }
        }

        if (false === empty($changeSet)) {
            $this->messageBus->dispatch(new ClientUpdated($client, $changeSet));
        }
    }
}
