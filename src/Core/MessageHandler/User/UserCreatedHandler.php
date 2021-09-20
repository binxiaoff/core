<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\User;

use KLS\Core\Entity\UserStatus;
use KLS\Core\Message\Message\User\UserCreated;
use KLS\Core\Repository\UserRepository;
use KLS\Core\Service\User\SlackNotifier\UserCreatedNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserCreatedHandler implements MessageHandlerInterface
{
    private UserCreatedNotifier $userCreatedNotifier;
    private UserRepository $userRepository;

    public function __construct(UserCreatedNotifier $userCreatedNotifier, UserRepository $userRepository)
    {
        $this->userCreatedNotifier = $userCreatedNotifier;
        $this->userRepository      = $userRepository;
    }

    /**
     * @throws \Http\Client\Exception
     * @throws \Nexy\Slack\Exception\SlackApiException
     */
    public function __invoke(UserCreated $userCreated)
    {
        $user = $this->userRepository->find($userCreated->getUserId());

        if (
            $user
            && UserStatus::STATUS_INVITED === $userCreated->getPreviousStatus()
            && UserStatus::STATUS_CREATED === $userCreated->getNewStatus()
        ) {
            foreach ($user->getStaff() as $staff) {
                if ($staff->isAdmin()) {
                    $this->userCreatedNotifier->notify($user);

                    break;
                }
            }
        }
    }
}
