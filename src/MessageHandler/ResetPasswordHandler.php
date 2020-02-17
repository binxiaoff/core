<?php

declare(strict_types=1);

namespace Unilend\MessageHandler;

use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\Request\ResetPassword;
use Unilend\Entity\TemporaryToken;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\TemporaryTokenRepository;
use Unilend\Service\Client\ClientNotifier;
use Unilend\Service\GoogleRecaptchaManager;

class ResetPasswordHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var ClientNotifier */
    private $notifier;
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;
    /** @var GoogleRecaptchaManager */
    private $googleRecaptchaManager;

    /**
     * @param ClientsRepository        $clientsRepository
     * @param ClientNotifier           $notifier
     * @param TemporaryTokenRepository $temporaryTokenRepository
     * @param GoogleRecaptchaManager   $googleRecaptchaManager
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        ClientNotifier $notifier,
        TemporaryTokenRepository $temporaryTokenRepository,
        GoogleRecaptchaManager $googleRecaptchaManager
    ) {
        $this->clientsRepository        = $clientsRepository;
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->notifier                 = $notifier;
        $this->googleRecaptchaManager   = $googleRecaptchaManager;
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

        if (
            false === $clients->isGrantedLogin()
            || false === $this->googleRecaptchaManager->isValid($resetPasswordRequest->captchaValue)
        ) {
            return;
        }

        $token = TemporaryToken::generateMediumToken($clients);

        $this->temporaryTokenRepository->save($token);

        $this->notifier->notifyPasswordRequest($token);
    }
}
