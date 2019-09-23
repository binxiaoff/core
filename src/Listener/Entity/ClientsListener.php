<?php

declare(strict_types=1);

namespace Unilend\Listener\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\Clients;
use Unilend\Message\Client\ClientUpdated;

class ClientsListener
{
    /** @var MessageBusInterface */
    private $messageBus;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param MessageBusInterface $messageBus
     * @param TranslatorInterface $translator
     */
    public function __construct(MessageBusInterface $messageBus, TranslatorInterface $translator)
    {
        $this->messageBus = $messageBus;
        $this->translator = $translator;
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
