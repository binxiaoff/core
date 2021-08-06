<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity;

use Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Core\Entity\User;

class UserPlainPasswordSubmittedListener
{
    /** @var UserPasswordEncoderInterface */
    private $userPasswordEncoder;

    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * @throws Exception
     */
    public function encodePlainPassword(User $user): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword(
                $this->userPasswordEncoder->encodePassword($user, $user->getPlainPassword())
            );

            $user->eraseCredentials();
        }
    }
}
