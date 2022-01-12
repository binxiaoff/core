<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\User;

use KLS\Core\Message\User\UserUpdated;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\User\UserNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserUpdatedHandler implements MessageHandlerInterface
{
    private UserRepository $userRepository;
    private UserNotifier $userNotifier;

    public function __construct(UserRepository $userRepository, UserNotifier $userNotifier)
    {
        $this->userRepository = $userRepository;
        $this->userNotifier   = $userNotifier;
    }

    public function __invoke(UserUpdated $userUpdated): void
    {
        $user      = $this->userRepository->find($userUpdated->getUserId());
        $changeSet = $userUpdated->getChangeSet();

        if ($user && $changeSet) {
            //$this->userNotifier->sendIdentityUpdated($user, array_keys($changeSet));
        }
    }
}
