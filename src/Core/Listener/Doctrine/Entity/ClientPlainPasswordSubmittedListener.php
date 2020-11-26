<?php

declare(strict_types=1);

namespace Unilend\Core\Listener\Doctrine\Entity;

use Exception;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Core\Entity\Clients;

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
     *
     * @throws Exception
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
