<?php

declare(strict_types=1);

namespace Unilend\MessageHandler;

use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\Request\ResetPassword;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\Client\ClientNotifier;

class ResetPasswordHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var ObjectManager */
    private $manager;
    /** @var ClientNotifier */
    private $notifier;

    /**
     * @param ClientsRepository $clientsRepository
     * @param ClientNotifier    $notifier
     * @param ObjectManager     $manager
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        ClientNotifier $notifier,
        ObjectManager $manager
    ) {
        $this->clientsRepository = $clientsRepository;
        $this->manager           = $manager;
        $this->notifier          = $notifier;
    }

    /**
     * @param ResetPassword $resetPasswordRequest
     *
     * @throws Exception
     */
    public function __invoke(ResetPassword $resetPasswordRequest): void
    {
        $clients = $this->clientsRepository->findOneBy(['email' => $resetPasswordRequest->email]);

        if (!$clients) {
            return;
        }

        if (false === $clients->isGrantedLogin()) {
            return;
        }

        $this->manager->persist($clients->addTemporaryToken());
        $this->manager->flush();

        $requestData = get_object_vars($resetPasswordRequest);
        unset($requestData['email']);

        $this->notifier->notifyPasswordRequest($clients, $requestData);
    }
}
