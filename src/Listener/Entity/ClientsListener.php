<?php

declare(strict_types=1);

namespace Unilend\Listener\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;
use Unilend\Entity\Clients;
use Unilend\Message\Client\ClientUpdated;

class ClientsListener
{
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

        $phoneFields = ['mobile', 'phone'];
        foreach ($phoneFields as $phoneField) {
            if ($args->hasChangedField($phoneField) && $args->getOldValue($phoneField)->equals($args->getNewValue($phoneField))) {
                unset($changeSet[$phoneField]);
            }
        }

        if (false === empty($changeSet)) {
            $this->messageBus->dispatch(new ClientUpdated($client->getIdClient(), $changeSet));
        }
    }
}
