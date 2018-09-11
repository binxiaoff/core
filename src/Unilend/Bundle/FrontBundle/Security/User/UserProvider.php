<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\{UnsupportedUserException, UsernameNotFoundException};
use Symfony\Component\Security\Core\User\{UserInterface, UserProviderInterface};
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\{ClientManager, LenderManager, SlackManager, TermsOfSaleManager};
use Unilend\Bundle\FrontBundle\Service\NotificationDisplayManager;

class UserProvider implements UserProviderInterface
{
    /** @var EntityManagerInterface */
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
     * @param EntityManagerInterface     $entityManager
     * @param ClientManager              $clientManager
     * @param NotificationDisplayManager $notificationDisplayManager
     * @param LenderManager              $lenderManager
     * @param SlackManager               $slackManager
     * @param TermsOfSaleManager         $termsOfSaleManager
     * @param LoggerInterface            $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
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
            $users      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findGrantedLoginAccountsByEmail($username);
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

                return $clientEntity;
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
        if (false === $this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        // The user must be reloaded via the primary key as all other data
        // might have changed without proper persistence in the database.
        // That's the case when the user has been changed by a form with
        // validation errors.
        $id = $user->getIdClient();
        if (empty($id)) {
            throw new \InvalidArgumentException('You cannot refresh a user ' .
                'from the EntityUserProvider that does not contain an identifier. ' .
                'The user object has to be serialized with its own identifier ' .
                'mapped by Doctrine.'
            );
        }

        $refreshedUser = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($id);
        if (null === $refreshedUser) {
            throw new UsernameNotFoundException(sprintf('User with id %s not found', json_encode($id)));
        }

        return $refreshedUser;
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class): bool
    {
        return $class === Clients::class;
    }
}
