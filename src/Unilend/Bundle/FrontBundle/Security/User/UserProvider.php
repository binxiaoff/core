<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\NotificationDisplayManager;

class UserProvider implements UserProviderInterface
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var  EntityManager */
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
     * UserProvider constructor.
     * @param EntityManagerSimulator     $entityManagerSimulator
     * @param EntityManager              $entityManager
     * @param ClientManager              $clientManager
     * @param NotificationDisplayManager $notificationDisplayManager
     * @param LenderManager              $lenderManager
     * @param ClientStatusManager        $clientStatusManager
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
        /** @var Clients $clientEntity */
        $clientEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);


            $initials = $this->clientManager->getClientInitials($client);
            $isActive = $this->clientManager->isActive($client);
            $roles    = ['ROLE_USER'];

            if ($clientEntity->isLender()) {/** @var Wallet $wallet */
                $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($clientEntity,WalletType::LENDER );
            $clientStatus            = $this->clientStatusManager->getLastClientStatus($client);
            $hasAcceptedCurrentTerms = $this->clientManager->hasAcceptedCurrentTerms($client);
            $notifications           = $this->notificationDisplayManager->getLastLenderNotifications($clientEntity);
            $userLevel               = $this->lenderManager->getDiversificationLevel($clientEntity);
            $roles[]                 = 'ROLE_LENDER';

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
                $client->lastlogin
            );
        }

        if ($clientEntity->isBorrower()) {
        /** @var Wallet $wallet */
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($clientEntity,WalletType::BORROWER);
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
            $wallet->getAvailableBalance(),
            $client->lastlogin
            );
        }
    }


    /**
     * @param string $hash
     *
     * @return UserBorrower|UserLender
     */
    public function loadUserByHash($hash)
    {
        if (1 !== preg_match('/^[a-z0-9-]{32,36}$/', $hash)) {
            throw new NotFoundHttpException('Invalid client hash');
        }

        /** @var \clients $client */
        $client = $this->entityManagerSimulator->getRepository('clients');

        if ($client->get($hash, 'status = ' . Clients::STATUS_ONLINE. ' AND hash')) {
            return $this->setUser($client);
        }

        throw new NotFoundHttpException(
            sprintf('Hash "%s" does not exist.', $hash)
        );
    }
}
