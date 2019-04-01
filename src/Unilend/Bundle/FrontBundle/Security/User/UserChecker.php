<?php declare(strict_types=1);

namespace Unilend\Bundle\FrontBundle\Security\User;

use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\{UserCheckerInterface, UserInterface};
use Unilend\Entity\Clients;

class UserChecker implements UserCheckerInterface
{
    /**
     * @inheritdoc
     */
    public function checkPreAuth(UserInterface $user): void
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
    public function checkPostAuth(UserInterface $user): void
    {
        // nothing to check
    }
}
