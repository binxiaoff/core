<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
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

    /**
     * @inheritDoc
     */
    public function __construct(EntityManager $entityManager, ClientManager $clientManager, NotificationDisplayManager $notificationDisplayManager, LenderManager $lenderManager)
    {
        $this->entityManager              = $entityManager;
        $this->clientManager              = $clientManager;
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->lenderManager              = $lenderManager;
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
        /** @var \clients_history $clientHistory */
        $clientHistory = $this->entityManager->getRepository('clients_history');

        if ($client->get($username, 'email')) {
            $balance       = $this->clientManager->getClientBalance($client);
            $initials      = $this->clientManager->getClientInitials($client);
            $isActive      = $this->clientManager->isActive($client);
            $roles         = ['ROLE_USER'];
            try {
                $lastLoginDate = $clientHistory->getClientLastLogin($client->id_client);
            } catch (\Exception $exception) {
                $lastLoginDate = null;
            }


            if ($this->clientManager->isLender($client)) {
                $lenderAccount->get($client->id_client, 'id_client_owner');

                $roles[]                 = 'ROLE_LENDER';
                $clientStatus            = $this->clientManager->getCurrentClientStatus($client);
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
