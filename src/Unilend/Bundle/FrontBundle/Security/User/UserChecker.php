<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\{UserCheckerInterface, UserInterface};
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class UserChecker implements UserCheckerInterface
{
    /**
     * @inheritdoc
     */
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof Clients) {
            return;
        }

        if (false === $user->isGrantedLogin()) {
            $exception = new DisabledException('User account is disabled.');
            $exception->setUser($user);
            throw $exception;
        }
    }

    /**
     * @inheritdoc
     */
    public function checkPostAuth(UserInterface $user)
    {
        // nothing to check
    }
}
