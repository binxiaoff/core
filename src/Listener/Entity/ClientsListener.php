<?php

declare(strict_types=1);

namespace Unilend\Listener\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\Clients;
use Unilend\Message\Client\ClientUpdated;
use Unilend\Service\MailerManager;

class ClientsListener
{
    /** @var MailerManager */
    private $mailerManager;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var MessageBusInterface */
    private $messageBus;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param MailerManager          $mailerManager
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface    $messageBus
     * @param TranslatorInterface    $translator
     */
    public function __construct(MailerManager $mailerManager, EntityManagerInterface $entityManager, MessageBusInterface $messageBus, TranslatorInterface $translator)
    {
        $this->mailerManager = $mailerManager;
        $this->entityManager = $entityManager;
        $this->messageBus    = $messageBus;
        $this->translator    = $translator;
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
