<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\NotificationDisplayManager;

class UserProvider implements UserProviderInterface
{
    /** @var EntityManager */
    private $entityManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
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
        EntityManagerSimulator $entityManagerSimulator,
        ClientManager $clientManager,
        NotificationDisplayManager $notificationDisplayManager,
        LenderManager $lenderManager,
        ClientStatusManager $clientStatusManager
    )
    {
        $this->entityManager              = $entityManager;
        $this->entityManagerSimulator     = $entityManagerSimulator;
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
        if (
            false !== filter_var($username, FILTER_VALIDATE_EMAIL)
            && ($clientEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['email' => $username, 'status' => Clients::STATUS_ONLINE]))
        ) {
           return $this->setUser($clientEntity);
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
     * @param Clients $clientEntity
     *
     * @return UserBorrower|UserLender|UserPartner
     */
    private function setUser(Clients $clientEntity)
    {
        /** @var \clients $client */
        $client = $this->entityManagerSimulator->getRepository('clients');
        $client->get($clientEntity->getIdClient());

        $balance  = $this->clientManager->getClientBalance($client);
        $initials = $this->clientManager->getClientInitials($client);
        $isActive = $this->clientManager->isActive($client);
        $roles    = ['ROLE_USER'];

        try {
            /** @var \clients_history $clientHistory */
            $clientHistory = $this->entityManagerSimulator->getRepository('clients_history');
            $lastLoginDate = $clientHistory->getClientLastLogin($clientEntity->getIdClient());
        } catch (\Exception $exception) {
            $lastLoginDate = null;
        }

        if ($this->clientManager->isLender($clientEntity)) {
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
            $lenderAccount->get($clientEntity->getIdClient(), 'id_client_owner');

            $roles[]                 = 'ROLE_LENDER';
            $clientStatus            = $this->clientStatusManager->getLastClientStatus($clientEntity);
            $hasAcceptedCurrentTerms = $this->clientManager->hasAcceptedCurrentTerms($client);
            $notifications           = $this->notificationDisplayManager->getLastLenderNotifications($lenderAccount);
            $userLevel               = $this->lenderManager->getDiversificationLevel($lenderAccount);

            return new UserLender(
                $clientEntity->getEmail(),
                $clientEntity->getPassword(),
                $clientEntity->getEmail(),
                '',
                $roles,
                $isActive,
                $clientEntity->getIdClient(),
                $clientEntity->getHash(),
                $balance,
                $initials,
                $clientEntity->getPrenom(),
                $clientEntity->getNom(),
                $clientStatus,
                $hasAcceptedCurrentTerms,
                $notifications,
                $clientEntity->getEtapeInscriptionPreteur(),
                $userLevel,
                $lastLoginDate
            );
        }

        if ($this->clientManager->isBorrower($clientEntity)) {
            /** @var \companies $company */
            $company = $this->entityManagerSimulator->getRepository('companies');
            $company->get($clientEntity->getIdClient(), 'id_client_owner');

            $roles[] = 'ROLE_BORROWER';
            return new UserBorrower(
                $clientEntity->getEmail(),
                $clientEntity->getPassword(),
                $clientEntity->getEmail(),
                '',
                $roles,
                $isActive,
                $clientEntity->getIdClient(),
                $clientEntity->getHash(),
                $clientEntity->getPrenom(),
                $clientEntity->getNom(),
                $company->siren,
                $lastLoginDate
            );
        }

        if (
            $this->clientManager->isPartner($clientEntity)
            && ($partnerRole = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient')->findOneBy(['idClient' => $clientEntity]))
        ) {
            $roles[] = 'ROLE_PARTNER';
            $roles[] = $partnerRole->getRole();

            return new UserPartner(
                $clientEntity->getEmail(),
                $clientEntity->getPassword(),
                $clientEntity->getEmail(),
                '',
                $roles,
                $isActive,
                $clientEntity->getIdClient(),
                $clientEntity->getHash(),
                $clientEntity->getPrenom(),
                $clientEntity->getNom(),
                $partnerRole->getIdCompany(),
                $lastLoginDate
            );
        }
    }

    public function loadUserByHash($hash)
    {
        if (1 !== preg_match('/^[a-z0-9-]{32,36}$/', $hash)) {
            throw new NotFoundHttpException('Invalid client hash');
        }

        if ($clientEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $hash, 'status' => Clients::STATUS_ONLINE])) {
            return $this->setUser($clientEntity);
        }

        throw new NotFoundHttpException(
            sprintf('Hash "%s" does not exist.', $hash)
        );
    }
}
