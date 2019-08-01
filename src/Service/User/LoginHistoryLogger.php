<?php

declare(strict_types=1);

namespace Unilend\Service\User;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{Clients, ClientsHistory, LoginLog};
use Unilend\Service\{UserActivity\IpGeoLocManager, UserActivity\UserAgentManager};

class LoginHistoryLogger
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var IpGeoLocManager */
    private $ipGeoLocManager;
    /** @var UserAgentManager */
    private $userAgentManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param IpGeoLocManager        $ipGeoLocManager
     * @param UserAgentManager       $userAgentManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, IpGeoLocManager $ipGeoLocManager, UserAgentManager $userAgentManager, LoggerInterface $logger)
    {
        $this->entityManager    = $entityManager;
        $this->ipGeoLocManager  = $ipGeoLocManager;
        $this->userAgentManager = $userAgentManager;
        $this->logger           = $logger;
    }

    /**
     * @param Clients     $client
     * @param string|null $ip
     * @param string|null $userAgent
     */
    public function saveSuccessfulLogin(Clients $client, ?string $ip, ?string $userAgent): void
    {
        try {
            $client->setLastLogin(new DateTimeImmutable());

            try {
                $this->entityManager->flush($client);
            } catch (\Exception $exception) {
                $this->logger->error('Could not save client last login date. Error: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                ]);
            }

            $userAgentHistory = null;
            if (null !== $userAgent) {
                try {
                    $userAgentHistory = $this->userAgentManager->saveClientUserAgent($client, $userAgent);
                } catch (\Exception $exception) {
                    $userAgentHistory = null;
                    $this->logger->error('An error occurred while trying to save user agent data. Exception: ' . $exception->getMessage(), [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_client'  => $client->getIdClient(),
                        'user_agent' => $userAgentHistory,
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine(),
                    ]);
                }
            }

            $clientHistory = new ClientsHistory();
            $clientHistory
                ->setIdClient($client)
                ->setStatus(ClientsHistory::STATUS_ACTION_LOGIN)
                ->setIp($ip)
                ->setUserAgentHistory($userAgentHistory)
            ;

            $this->entityManager->persist($clientHistory);
            $this->entityManager->flush($clientHistory);
        } catch (\Exception $exception) {
            $this->logger->error('An error occurred while saving user login. Exception: ' . $exception->getMessage(), [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'id_client' => $client->getIdClient(),
                'ip'        => $ip,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
            ]);
        }
    }

    /**
     * @param string $pseudo
     * @param string $ip
     * @param string $message
     *
     * @return LoginLog|null
     */
    public function saveFailureLogin(string $pseudo, ?string $ip, string $message): ?LoginLog
    {
        try {
            $loginLog = new LoginLog();
            $loginLog
                ->setPseudo($pseudo)
                ->setIp($ip)
                ->setRetour($message)
            ;

            $this->entityManager->persist($loginLog);
            $this->entityManager->flush($loginLog);

            return $loginLog;
        } catch (\Exception $exception) {
            $this->logger->error('An error occurred while saving login failure. Exception: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'pseudo'   => $pseudo,
                'ip'       => $ip,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine(),
            ]);

            return null;
        }
    }
}
