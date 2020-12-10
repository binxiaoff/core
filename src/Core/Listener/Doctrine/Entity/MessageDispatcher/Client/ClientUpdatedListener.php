<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\Client;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Listener\Doctrine\Entity\MessageDispatcher\MessageDispatcherTrait;
use Unilend\Core\Message\Client\ClientUpdated;

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
            if (false === in_array($updatedField, self::MONITORED_FIELDS, true)) {
                unset($changeSet[$updatedField]);
            }
        }

        if (false === empty($changeSet)) {
            $this->messageBus->dispatch(new ClientUpdated($client, $changeSet));
        }
    }
}
