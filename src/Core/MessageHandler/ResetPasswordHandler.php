<?php

declare(strict_types=1);

namespace Unilend\Core\MessageHandler;

use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Core\Entity\Request\ResetPassword;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Service\GoogleRecaptchaManager;
use Unilend\Core\Service\User\UserNotifier;

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
