<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\NotificationDisplayManager;

class UserProvider implements UserProviderInterface
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var ClientManager */
    private $clientManager;
    /** @var NotificationDisplayManager */
    private $notificationDisplayManager;
    /** @var LenderManager */
    private $lenderManager;
    /** @var ClientStatusManager */
    private $clientStatusManager;

    /**
     * @inheritDoc
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        ClientManager $clientManager,
        NotificationDisplayManager $notificationDisplayManager,
        LenderManager $lenderManager,
        ClientStatusManager $clientStatusManager
    ) {
        $this->entityManagerSimulator     = $entityManagerSimulator;
        $this->entityManager              = $entityManager;
        $this->clientManager              = $clientManager;
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->lenderManager              = $lenderManager;
        $this->clientStatusManager        = $clientStatusManager;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        /** @var \clients $client */
        $client = $this->entityManagerSimulator->getRepository('clients');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \clients_history $clientHistory */
        $clientHistory = $this->entityManagerSimulator->getRepository('clients_history');

        if (false !== filter_var($username, FILTER_VALIDATE_EMAIL) && $client->get($username, 'status = ' . Clients::STATUS_ONLINE. ' AND email')) {
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
                $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);

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
                    $wallet->getAvailableBalance(),
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
                $company = $this->entityManagerSimulator->getRepository('companies');
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
