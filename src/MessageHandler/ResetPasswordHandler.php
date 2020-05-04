<?php

declare(strict_types=1);

namespace Unilend\MessageHandler;

use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\Request\ResetPassword;
use Unilend\Repository\ClientsRepository;
use Unilend\Service\{Client\ClientNotifier, GoogleRecaptchaManager};

class ResetPasswordHandler implements MessageHandlerInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var ClientNotifier */
    private $notifier;
    /** @var GoogleRecaptchaManager */
    private $googleRecaptchaManager;

    /**
     * @param ClientsRepository      $clientsRepository
     * @param ClientNotifier         $notifier
     * @param GoogleRecaptchaManager $googleRecaptchaManager
     */
    public function __construct(
        ClientsRepository $clientsRepository,
        ClientNotifier $notifier,
        GoogleRecaptchaManager $googleRecaptchaManager
    ) {
        $this->clientsRepository      = $clientsRepository;
        $this->notifier               = $notifier;
        $this->googleRecaptchaManager = $googleRecaptchaManager;
    }

    /**
     * @param ResetPassword $resetPasswordRequest
     *
     * @throws Exception
     */
    public function __invoke(ResetPassword $resetPasswordRequest): void
    {
        $client = $this->clientsRepository->findOneBy(['email' => $resetPasswordRequest->email]);

        if ($client && $this->googleRecaptchaManager->isValid($resetPasswordRequest->captchaValue)) {
            $this->notifier->notifyPasswordRequest($client);
        }
    }
}
