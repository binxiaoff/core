<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\NotificationDisplayManager;

class UserProvider implements UserProviderInterface
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ClientManager */
    private $clientManager;
    /** @var NotificationDisplayManager */
    private $notificationDisplayManager;
    /** @var LenderManager */
    private $lenderManager;
    /** @var  ClientStatusManager */
    private $clientStatusManager;

    /**
     * @inheritDoc
     */
    public function __construct(
        EntityManager $entityManager,
        ClientManager $clientManager,
        NotificationDisplayManager $notificationDisplayManager,
        LenderManager $lenderManager,
        ClientStatusManager $clientStatusManager
    ) {
        $this->entityManager = $entityManager;
        $this->clientManager = $clientManager;
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->lenderManager = $lenderManager;
        $this->clientStatusManager = $clientStatusManager;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');

        if (false !== filter_var($username, FILTER_VALIDATE_EMAIL) && $client->get($username, 'status = ' . Clients::STATUS_ONLINE. ' AND email')) {
            $balance  = $this->clientManager->getClientBalance($client);
            $initials = $this->clientManager->getClientInitials($client);
            $isActive = $this->clientManager->isActive($client);
            $roles    = ['ROLE_USER'];

            if ($this->clientManager->isLender($client)) {
                $lenderAccount->get($client->id_client, 'id_client_owner');

                $roles[]                 = 'ROLE_LENDER';
                $clientStatus            = $this->clientStatusManager->getLastClientStatus($client);
                $hasAcceptedCurrentTerms = $this->clientManager->hasAcceptedCurrentTerms($client);
                $notifications           = $this->notificationDisplayManager->getLastLenderNotifications($lenderAccount);
                $userLevel               = $this->lenderManager->getDiversificationLevel($lenderAccount);

                return new UserLender(
                    $client->email,
                    $client->password,
                    $client->email,
                    '',
                    $roles,
                    $isActive,
                    $client->id_client,
                    $client->hash,
                    $balance,
                    $initials,
                    $client->prenom,
                    $client->nom,
                    $clientStatus,
                    $hasAcceptedCurrentTerms,
                    $notifications,
                    $client->etape_inscription_preteur,
                    $userLevel,
                    $client->lastlogin
                );
            }

            if ($this->clientManager->isBorrower($client)) {
                /** @var \companies $company */
                $company = $this->entityManager->getRepository('companies');
                $company->get($client->id_client, 'id_client_owner');
                $roles[] = 'ROLE_BORROWER';
                return new UserBorrower(
                    $client->email,
                    $client->password,
                    $client->email,
                    '',
                    $roles,
                    $isActive,
                    $client->id_client,
                    $client->hash,
                    $client->prenom,
                    $client->nom,
                    $company->siren,
                    $lastLoginDate
                );
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
        return $class === 'FrontBundle\Security\User\BaseUser';
    }
}
