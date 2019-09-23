<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Client;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Message\Client\ClientUpdated;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\MailerManager;

class ClientUpdatedHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var MailerManager */
    private $mailerManager;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param ClientsRepository   $clientsRepository
     * @param MailerManager       $mailerManager
     * @param TranslatorInterface $translator
     */
    public function __construct(ClientsRepository $clientsRepository, MailerManager $mailerManager, TranslatorInterface $translator)
    {
        $this->clientsRepository = $clientsRepository;
        $this->mailerManager     = $mailerManager;
        $this->translator        = $translator;
    }

    /**
     * @param ClientUpdated $clientUpdated
     */
    public function __invoke(ClientUpdated $clientUpdated)
    {
        $client    = $this->clientsRepository->find($clientUpdated->getClientId());
        $changeSet = $clientUpdated->getChangeSet();

        foreach ($changeSet as $field => $value) {
            $changeSet[$field] = $this->translator->trans('mail-identity-updated.' . $field);
        }

        $this->mailerManager->sendIdentityUpdated($client, $changeSet);
    }
}
