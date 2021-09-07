<?php

declare(strict_types=1);

namespace KLS\Core\Listener\Doctrine\Entity;

use Exception;
use KLS\Core\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
