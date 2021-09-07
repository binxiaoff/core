<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler;

use Exception;
use KLS\Core\Entity\Request\ResetPassword;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\GoogleRecaptchaManager;
use KLS\Core\Service\User\UserNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ResetPasswordHandler implements MessageHandlerInterface
{
    /** @var UserRepository */
    private $userRepository;
    /** @var UserNotifier */
    private $notifier;
    /** @var GoogleRecaptchaManager */
    private $googleRecaptchaManager;

    public function __construct(
        UserRepository $userRepository,
        UserNotifier $notifier,
        GoogleRecaptchaManager $googleRecaptchaManager
    ) {
        $this->userRepository         = $userRepository;
        $this->notifier               = $notifier;
        $this->googleRecaptchaManager = $googleRecaptchaManager;
    }

    /**
     * @throws Exception
     */
    public function __invoke(ResetPassword $resetPasswordRequest): void
    {
        $user = $this->userRepository->findOneBy(['email' => $resetPasswordRequest->email]);

        if ($user && $this->googleRecaptchaManager->getResult($resetPasswordRequest->captchaValue)->valid) {
            $this->notifier->notifyPasswordRequest($user);
        }
    }
}
