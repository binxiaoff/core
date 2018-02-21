<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Service\SlackManager;
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
    /** @var SlackManager */
    private $slackManager;

    /**
     * UserProvider constructor.
     * @param EntityManagerSimulator     $entityManagerSimulator
     * @param EntityManager              $entityManager
     * @param ClientManager              $clientManager
     * @param NotificationDisplayManager $notificationDisplayManager
     * @param LenderManager              $lenderManager
     * @param SlackManager               $slackManager
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        ClientManager $clientManager,
        NotificationDisplayManager $notificationDisplayManager,
        LenderManager $lenderManager,
        SlackManager $slackManager
    )
    {
        $this->entityManagerSimulator     = $entityManagerSimulator;
        $this->entityManager              = $entityManager;
        $this->clientManager              = $clientManager;
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->lenderManager              = $lenderManager;
        $this->slackManager               = $slackManager;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        if (false !== filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $users      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findBy(['email' => $username, 'status' => Clients::STATUS_ONLINE]);
            $usersCount = count($users);

            if ($usersCount > 0) {
                $clientEntity = current($users);

                if ($usersCount > 1) {
                    $ids = [];
                    foreach ($users as $user) {
                        $ids[] = $user->getIdClient();
                    }

                    $this->slackManager->sendMessage('[Connexion] L’adresse email ' . $username . ' est en doublon (' . $usersCount . ' occurrences : ' . implode(', ', $ids) . ')', '#doublons-email');
                }

                return $this->setUser($clientEntity);
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

        $initials = $this->clientManager->getClientInitials($client);
        $isActive = $this->clientManager->isActive($client);
        $roles    = ['ROLE_USER'];

        if ($clientEntity->isLender()) {
            /** @var Wallet $wallet */
            $wallet                  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($clientEntity, WalletType::LENDER);
            /** @var ClientsStatus $clientStatusEntity */
            $clientStatusEntity      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatus')->getLastClientStatus($client->id_client);
            $hasAcceptedCurrentTerms = $this->clientManager->hasAcceptedCurrentTerms($client);
            $notifications           = $this->notificationDisplayManager->getLastLenderNotifications($clientEntity);
            $userLevel               = $this->lenderManager->getDiversificationLevel($clientEntity);
            $roles[]                 = 'ROLE_LENDER';

            return new UserLender(
                $clientEntity->getEmail(),
                $clientEntity->getPassword(),
                $clientEntity->getEmail(),
                '',
                $roles,
                $isActive,
                $clientEntity->getIdClient(),
                $clientEntity->getHash(),
                $wallet->getAvailableBalance(),
                $initials,
                $clientEntity->getPrenom(),
                $clientEntity->getNom(),
                (null === $clientStatusEntity) ? null : $clientStatusEntity->getStatus(),
                $hasAcceptedCurrentTerms,
                $notifications,
                $clientEntity->getEtapeInscriptionPreteur(),
                $userLevel,
                $clientEntity->getLastlogin()
            );
        }

        if ($clientEntity->isBorrower()) {
            /** @var Wallet $wallet */
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($clientEntity, WalletType::BORROWER);
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
                $wallet->getAvailableBalance(),
                $clientEntity->getLastlogin()
            );
        }

        if (
            $clientEntity->isPartner()
            && ($partnerRole = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient')->findOneBy(['idClient' => $clientEntity]))
        ) {
            $roles[] = UserPartner::ROLE_DEFAULT;
            $roles[] = $partnerRole->getRole();

            $rootCompany = $partnerRole->getIdCompany();

            while ($rootCompany->getIdParentCompany() && $rootCompany->getIdParentCompany()->getIdCompany()) {
                $rootCompany = $rootCompany->getIdParentCompany();
            }

            $partner = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findOneBy(['idCompany' => $rootCompany->getIdCompany()]);

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
                $partner,
                $clientEntity->getLastlogin()
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

        if ($clientEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['hash' => $hash, 'status' => Clients::STATUS_ONLINE])) {
            return $this->setUser($clientEntity);
        }

        throw new NotFoundHttpException(
            sprintf('Hash "%s" does not exist.', $hash)
        );
    }
}
