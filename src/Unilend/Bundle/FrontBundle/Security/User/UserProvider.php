<?php

namespace Unilend\Bundle\FrontBundle\Security\User;


use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class UserProvider implements UserProviderInterface
{
    /** @var  EntityManager */
    private $entityManager;
    /** @var  ClientManager */
    private $clientManager;

    /**
     * @inheritDoc
     */
    public function __construct($entityManager, $clientManager)
    {
        $this->entityManager = $entityManager;
        $this->clientManager = $clientManager;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        if ($client->get($username, 'email')) {
            $balance                 = $this->clientManager->getClientBalance($client);
            $initials                = $this->clientManager->getClientInitials($client);
            $roles                   = ['ROLE_USER'];

            if ($this->clientManager->isLender($client)) {
                $roles[] = 'ROLE_LENDER';
                $isActive                = $this->clientManager->isActive($client);
                $clientStatus            = $this->clientManager->getCurrentClientStatus($client);
                $hasAcceptedCurrentTerms = $this->clientManager->hasAcceptedCurrentTerms($client);
                return new UserLender($client->email, $client->password, '', $roles, $balance, $initials, $client->prenom, $isActive, $clientStatus, $hasAcceptedCurrentTerms);
            }

            if ($this->clientManager->isBorrower($client)) {
                $roles[] = 'ROLE_BORROWER';
                //return new UserBorrower($client->email, $client->password, '', $roles, $balance, $initials, $client->prenom, $isActive, $clientStatus, $hasAcceptedCurrentTerms);
            }
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );

    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user)
    {
        if (false === $user instanceof BaseUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class)
    {
        return $class === 'FrontBundle\Security\User\User';
    }


}