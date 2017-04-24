<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

        if (false !== filter_var($username, FILTER_VALIDATE_EMAIL) && $client->get($username, 'status = ' . Clients::STATUS_ONLINE. ' AND email')) {
           return $this->setUser($client);
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

        return $this->loadUserByHash($user->getHash());
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class)
    {
        return $class === 'FrontBundle\Security\User\BaseUser';
    }

    /**
     * @param \clients $client
     *
     * @return UserBorrower|UserLender
     */
    private function setUser(\clients $client)
    {
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        /** @var \clients_history $clientHistory */
        $clientHistory = $this->entityManager->getRepository('clients_history');

        $balance  = $this->clientManager->getClientBalance($client);
        $initials = $this->clientManager->getClientInitials($client);
        $isActive = $this->clientManager->isActive($client);
        $roles    = ['ROLE_USER'];

        try {
            $lastLoginDate = $clientHistory->getClientLastLogin($client->id_client);
        } catch (\Exception $exception) {
            $lastLoginDate = null;
        }

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
                $lastLoginDate
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

    public function loadUserByHash($hash)
    {
        if (1 !== preg_match('/^[a-z0-9-]{32,36}$/', $hash)) {
            throw new NotFoundHttpException('Invalid client hash');
        }

        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');

        if ($client->get($hash, 'status = ' . Clients::STATUS_ONLINE. ' AND hash')) {
            return $this->setUser($client);
        }

        throw new NotFoundHttpException(
            sprintf('Hash "%s" does not exist.', $hash)
        );
    }
}
