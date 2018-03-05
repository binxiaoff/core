<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\{
    UnsupportedUserException, UsernameNotFoundException
};
use Symfony\Component\Security\Core\User\{
    UserInterface, UserProviderInterface
};
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus, Wallet, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\{
    ClientManager, LenderManager, SlackManager, TermsOfSaleManager
};
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
    /** @var SlackManager */
    private $slackManager;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;

    /**
     * @param EntityManagerSimulator     $entityManagerSimulator
     * @param EntityManager              $entityManager
     * @param ClientManager              $clientManager
     * @param NotificationDisplayManager $notificationDisplayManager
     * @param LenderManager              $lenderManager
     * @param SlackManager               $slackManager
     * @param TermsOfSaleManager         $termsOfSaleManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        ClientManager $clientManager,
        NotificationDisplayManager $notificationDisplayManager,
        LenderManager $lenderManager,
        SlackManager $slackManager,
        TermsOfSaleManager $termsOfSaleManager
    )
    {
        $this->entityManagerSimulator     = $entityManagerSimulator;
        $this->entityManager              = $entityManager;
        $this->clientManager              = $clientManager;
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->lenderManager              = $lenderManager;
        $this->slackManager               = $slackManager;
        $this->termsOfSaleManager         = $termsOfSaleManager;
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
     * @param Clients $client
     *
     * @return UserBorrower|UserLender|UserPartner
     */
    private function setUser(Clients $client)
    {
        $initials = $this->clientManager->getInitials($client);
        $isActive = $this->clientManager->isActive($client);
        $roles    = ['ROLE_USER'];

        if ($client->isLender()) {
            /** @var Wallet $wallet */
            $wallet                  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
            /** @var ClientsStatus $clientStatusEntity */
            $clientStatusEntity      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatus')->getLastClientStatus($client->getIdClient());
            $hasAcceptedCurrentTerms = $this->termsOfSaleManager->hasAcceptedCurrentVersion($client);
            $notifications           = $this->notificationDisplayManager->getLastLenderNotifications($client);
            $userLevel               = $this->lenderManager->getDiversificationLevel($client);
            $roles[]                 = 'ROLE_LENDER';

            return new UserLender(
                $client->getEmail(),
                $client->getPassword(),
                $client->getEmail(),
                '',
                $roles,
                $isActive,
                $client->getIdClient(),
                $client->getHash(),
                $wallet->getAvailableBalance(),
                $initials,
                $client->getPrenom(),
                $client->getNom(),
                (null === $clientStatusEntity) ? null : $clientStatusEntity->getStatus(),
                $hasAcceptedCurrentTerms,
                $notifications,
                $client->getEtapeInscriptionPreteur(),
                $userLevel,
                $client->getLastlogin()
            );
        }

        if ($client->isBorrower()) {
            /** @var Wallet $wallet */
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::BORROWER);
            /** @var \companies $company */
            $company = $this->entityManagerSimulator->getRepository('companies');
            $company->get($client->getIdClient(), 'id_client_owner');
            $roles[] = 'ROLE_BORROWER';

            return new UserBorrower(
                $client->getEmail(),
                $client->getPassword(),
                $client->getEmail(),
                '',
                $roles,
                $isActive,
                $client->getIdClient(),
                $client->getHash(),
                $client->getPrenom(),
                $client->getNom(),
                $company->siren,
                $wallet->getAvailableBalance(),
                $client->getLastlogin()
            );
        }

        if (
            $client->isPartner()
            && ($partnerRole = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient')->findOneBy(['idClient' => $client]))
        ) {
            $roles[] = UserPartner::ROLE_DEFAULT;
            $roles[] = $partnerRole->getRole();

            $rootCompany = $partnerRole->getIdCompany();

            while ($rootCompany->getIdParentCompany() && $rootCompany->getIdParentCompany()->getIdCompany()) {
                $rootCompany = $rootCompany->getIdParentCompany();
            }

            $partner = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findOneBy(['idCompany' => $rootCompany->getIdCompany()]);

            return new UserPartner(
                $client->getEmail(),
                $client->getPassword(),
                $client->getEmail(),
                '',
                $roles,
                $isActive,
                $client->getIdClient(),
                $client->getHash(),
                $client->getPrenom(),
                $client->getNom(),
                $partnerRole->getIdCompany(),
                $partner,
                $client->getLastlogin()
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
