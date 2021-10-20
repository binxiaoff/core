<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler;

use Exception;
use KLS\Core\Entity\Request\ResetPassword;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\User\UserNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ResetPasswordHandler implements MessageHandlerInterface
{
    private UserRepository $userRepository;

    private UserNotifier            $notifier;

    public function __construct(UserRepository $userRepository, UserNotifier $notifier)
    {
        $this->userRepository = $userRepository;
        $this->notifier       = $notifier;
    }

    /**
     * @throws Exception
     */
    public function __invoke(ResetPassword $resetPasswordRequest): void
    {
        $user = $this->userRepository->findOneBy(['email' => $resetPasswordRequest->email]);

        if ($user) {
            $this->notifier->notifyPasswordRequest($user);
        }
    }
}
