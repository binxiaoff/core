<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ClientsHistory, LoginLog};
use Unilend\Bundle\CoreBusinessBundle\Service\{UserActivity\IpGeoLocManager, UserActivity\UserAgentManager};

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
            $client->setLastlogin(new \DateTime('NOW'));

            $isLender   = $client->isLender();
            $isBorrower = $client->isBorrower();
            $isPartner  = $client->isPartner();

            if ($isLender && $isBorrower) {
                $type = ClientsHistory::TYPE_CLIENT_LENDER_BORROWER;
            } elseif ($isLender) {
                $type = ClientsHistory::TYPE_CLIENT_LENDER;
            } elseif ($isBorrower) {
                $type = ClientsHistory::TYPE_CLIENT_BORROWER;
            } elseif ($isPartner) {
                $type = ClientsHistory::TYPE_CLIENT_PARTNER;
            }

            $userAgentEntity = null;
            if (null !== $userAgent) {
                try {
                    $userAgentEntity = $this->userAgentManager->saveClientUserAgent($client, $userAgent);
                } catch (\Exception $exception) {
                    $userAgentEntity = null;
                    $this->logger->error('An error occurred while trying to save user agent data. Exception: ' . $exception->getMessage(), [
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'id_client'  => $client->getIdClient(),
                        'user_agent' => $userAgentEntity,
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine(),
                    ]);
                }
            }

            $clientHistory = new ClientsHistory();
            $clientHistory
                ->setIdClient($client)
                ->setType($type)
                ->setStatus(ClientsHistory::STATUS_ACTION_LOGIN)
                ->setIp($ip)
                ->setIdUserAgent($userAgentEntity);

            $geoLocData = null;
            if ($ip) {
                $geoLocData = $this->ipGeoLocManager->getCountryAndCity($ip);
            }
            if (is_array($geoLocData)) {
                $clientHistory
                    ->setCity($geoLocData['city'])
                    ->setCountryIsoCode($geoLocData['countryIsoCode']);
            }

            $this->entityManager->persist($clientHistory);
            $this->entityManager->flush($clientHistory);
        } catch (\Exception $exception) {
            $this->logger->error('An error occurred while saving user login. Exception: ' . $exception->getMessage(), [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'id_client' => $client->getIdClient(),
                'ip'        => $ip,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
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
                ->setRetour($message);

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
                'line'     => $exception->getLine()
            ]);

            return null;
        }
    }
}
