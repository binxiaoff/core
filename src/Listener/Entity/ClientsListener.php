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

        foreach ($changeSet as $field => $value) {
            if (('mobile' === $field || 'phone' === $field) && $changeSet[$field][0]->equals($changeSet[$field][1])) {
                unset($changeSet[$field]);
            }
        }

        if (false === empty($changeSet) && false === empty($client)) {
            foreach ($changeSet as $field => $value) {
                unset($changeSet[$field]);
                $changeSet[] = $this->translator->trans('mail-identity-updated.' . $field);
            }

            if (count($changeSet) > 1) {
                $content      = $this->translator->trans('mail-identity-updated.content-message-plural');
                $changeFields = '<ul><li>';
                $changeFields .= implode('</li><li>', $changeSet);
                $changeFields .= '</li></ul>';
            } else {
                $content      = $this->translator->trans('mail-identity-updated.content-message-singular');
                $changeFields = $changeSet[0];
            }

            $this->messageBus->dispatch(new ClientUpdated($client->getIdClient(), $content, $changeFields));
        }
    }
}
