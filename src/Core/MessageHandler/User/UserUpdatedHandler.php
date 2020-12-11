<?php

declare(strict_types=1);

namespace Unilend\Core\MessageHandler\User;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Core\Message\User\UserUpdated;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Service\User\UserNotifier;

class UserUpdatedHandler implements MessageHandlerInterface
{
    /** @var UserRepository */
    private $userRepository;
    /** @var UserNotifier */
    private $userNotifier;

    /**
     * @param UserRepository $userRepository
     * @param UserNotifier   $userNotifier
     */
    public function __construct(UserRepository $userRepository, UserNotifier $userNotifier)
    {
        $this->userRepository  = $userRepository;
        $this->userNotifier    = $userNotifier;
    }

    /**
     * @param UserUpdated $userUpdated
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(UserUpdated $userUpdated)
    {
        $user    = $this->userRepository->find($userUpdated->getUserId());
        $changeSet = $userUpdated->getChangeSet();

        if ($user && $changeSet) {
            //$this->userNotifier->sendIdentityUpdated($user, array_keys($changeSet));
        }
    }
}
