<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\User;

use KLS\Core\Entity\Request\UserInitialization;
use KLS\Core\Entity\User;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\User\UserInitializationNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserInitializationHandler implements MessageHandlerInterface
{
    private UserInitializationNotifier $userInitialisationNotifier;
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository, UserInitializationNotifier $userInitialisationNotifier)
    {
        $this->userRepository             = $userRepository;
        $this->userInitialisationNotifier = $userInitialisationNotifier;
    }

    public function __invoke(UserInitialization $userInitialization): void
    {
        $user = $this->userRepository->findOneBy(['email' => $userInitialization->email]);

        if ($user instanceof User) {
            $this->userInitialisationNotifier->notifyUserInitialization($user);
        }
    }
}
