<?php

declare(strict_types=1);

namespace Unilend\Listener\Doctrine\Entity;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Entity\Clients;

class ClientPlainPasswordSubmittedListener
{
    /** @var UserPasswordEncoderInterface */
    private $userPasswordEncoder;

    /**
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     */
    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * @param Clients $client
     */
    public function encodePlainPassword(Clients $client): void
    {
        if ($client->getPlainPassword()) {
            $client->setPassword(
                $this->userPasswordEncoder->encodePassword($client, $client->getPlainPassword())
            );

            $client->eraseCredentials();
        }
    }
}
