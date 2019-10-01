<?php

declare(strict_types=1);

namespace Unilend\Listener\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use libphonenumber\PhoneNumber;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\Clients;
use Unilend\Message\Client\ClientUpdated;

class ClientsListener
{
    private const MONITORED_FIELDS = [
        'lastName',
        'firstName',
        'phone',
        'mobile',
        'email',
        'jobFunction',
    ];
    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @param Clients            $client
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Clients $client, PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();

        foreach ($changeSet as $updatedField => $newValue) {
            if (false === in_array($updatedField, self::MONITORED_FIELDS, true)
                || ($args->getOldValue($updatedField) instanceof PhoneNumber
                    && $args->getNewValue($updatedField) instanceof PhoneNumber
                    && $args->getOldValue($updatedField)->equals($args->getNewValue($updatedField)))) {
                unset($changeSet[$updatedField]);
            }
        }

        if (false === empty($changeSet)) {
            $this->messageBus->dispatch(new ClientUpdated($client, $changeSet));
        }
    }
}
