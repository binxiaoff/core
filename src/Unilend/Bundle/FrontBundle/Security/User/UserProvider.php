<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\{
    UnsupportedUserException, UsernameNotFoundException
};
use Symfony\Component\Security\Core\User\{
    UserInterface, UserProviderInterface
};
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus, Companies, Wallet, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\{
    ClientManager, LenderManager, SlackManager, TermsOfSaleManager
};
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
    /** @var SlackManager */
    private $slackManager;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager              $entityManager
     * @param ClientManager              $clientManager
     * @param NotificationDisplayManager $notificationDisplayManager
     * @param LenderManager              $lenderManager
     * @param SlackManager               $slackManager
     * @param TermsOfSaleManager         $termsOfSaleManager
     * @param LoggerInterface            $logger
     */
    public function __construct(
        EntityManager $entityManager,
        ClientManager $clientManager,
        NotificationDisplayManager $notificationDisplayManager,
        LenderManager $lenderManager,
        SlackManager $slackManager,
        TermsOfSaleManager $termsOfSaleManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager              = $entityManager;
        $this->clientManager              = $clientManager;
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->lenderManager              = $lenderManager;
        $this->slackManager               = $slackManager;
        $this->termsOfSaleManager         = $termsOfSaleManager;
        $this->logger                     = $logger;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username): UserInterface
    {
        if (false !== filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $users      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findByEmailAndStatus($username, ClientsStatus::GRANTED_LOGIN);
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
    public function refreshUser(UserInterface $user): UserInterface
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
    public function supportsClass($class): bool
    {
        return $class === 'FrontBundle\Security\User\BaseUser';
    }

    /**
     * @param Clients $client
     *
     * @return UserInterface
     */
    private function setUser(Clients $client): UserInterface
    {
        $initials = $this->clientManager->getInitials($client);
        $roles    = ['ROLE_USER'];

        if ($client->isLender()) {
            $roles[] = 'ROLE_LENDER';

            /** @var Wallet $wallet */
            $wallet                  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
            $hasAcceptedCurrentTerms = $this->termsOfSaleManager->hasAcceptedCurrentVersion($client);

            try {
                $notifications = $this->notificationDisplayManager->getLastLenderNotifications($client);
            } catch (\Exception $exception) {
                $notifications = [];
                $this->logger->error(
                    'Unable to retrieve last lender notifications',
                    ['id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }

            try {
                $userLevel = $this->lenderManager->getDiversificationLevel($client);
            } catch (\Exception $exception) {
                $userLevel = 0;
                $this->logger->error(
                    'Unable to retrieve lender diversification level',
                    ['id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }

            return new UserLender(
                $client->getEmail(),
                $client->getPassword(),
                $client->getEmail(),
                '',
                $roles,
                $client->getIdClient(),
                $client->getHash(),
                $client->getIdClientStatusHistory()->getIdStatus()->getId(),
                $wallet->getAvailableBalance(),
                $initials,
                $client->getPrenom(),
                $client->getNom(),
                $hasAcceptedCurrentTerms,
                $notifications,
                $client->getEtapeInscriptionPreteur(),
                $userLevel,
                $client->getLastlogin()
            );
        }

        if ($client->isBorrower()) {
            $roles[] = 'ROLE_BORROWER';

            /** @var Wallet $wallet */
            $wallet  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::BORROWER);
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);

            return new UserBorrower(
                $client->getEmail(),
                $client->getPassword(),
                $client->getEmail(),
                '',
                $roles,
                $client->getIdClient(),
                $client->getHash(),
                $client->getIdClientStatusHistory()->getIdStatus()->getId(),
                $client->getPrenom(),
                $client->getNom(),
                $company->getSiren(),
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

            /** @var Companies $rootCompany */
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
                $client->getIdClient(),
                $client->getHash(),
                $client->getIdClientStatusHistory()->getIdStatus()->getId(),
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
     * @return UserInterface
     */
    public function loadUserByHash(string $hash): UserInterface
    {
        if (1 !== preg_match('/^[a-z0-9-]{32,36}$/', $hash)) {
            throw new NotFoundHttpException('Invalid client hash');
        }

        $clientEntity = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Clients')
            ->findOneByHashAndStatus($hash, ClientsStatus::GRANTED_LOGIN);

        if ($clientEntity) {
            return $this->setUser($clientEntity);
        }

        throw new NotFoundHttpException(
            sprintf('No client with hash "%s" can log in.', $hash)
        );
    }
}
