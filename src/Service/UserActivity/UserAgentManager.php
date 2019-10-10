<?php

declare(strict_types=1);

namespace Unilend\Service\UserActivity;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{Clients, UserAgentHistory};
use UserAgentParser\Model\UserAgent;
use UserAgentParser\Provider\Chain;

class UserAgentManager
{
    /** @var Chain */
    private $chain;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Chain                  $chain
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(Chain $chain, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->chain         = $chain;
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @param Clients     $client
     * @param string|null $userAgent
     *
     * @throws Exception
     *
     * @return UserAgentHistory|null
     */
    public function saveClientUserAgent(Clients $client, string $userAgent): ?UserAgentHistory
    {
        if ($parser = $this->parse($userAgent)) {
            $browser = $parser->getBrowser();
            $device  = $parser->getDevice();

            $knownUserAgent = $this->entityManager->getRepository(UserAgentHistory::class)
                ->findOneBy([
                    'idClient'    => $client,
                    'browserName' => $browser->getName(),
                    'deviceModel' => $device->getModel(),
                    'deviceBrand' => $device->getBrand(),
                    'deviceType'  => $device->getType(),
                ])
            ;

            if ($knownUserAgent) {
                return $knownUserAgent;
            }
            $newUserAgent = new UserAgentHistory();
            $newUserAgent
                ->setIdClient($client)
                ->setBrowserName($browser->getName())
                ->setBrowserVersion($browser->getVersion()->getComplete())
                ->setDeviceModel($device->getModel())
                ->setDeviceBrand($device->getBrand())
                ->setDeviceType(mb_strtolower($device->getType()))
                ->setUserAgentString($userAgent)
                ;

            $this->entityManager->persist($newUserAgent);
            $this->entityManager->flush($newUserAgent);

            return $newUserAgent;
        }

        return null;
    }

    /**
     * @param string $userAgent
     *
     * @return UserAgent|null
     */
    public function parse(string $userAgent): ?UserAgent
    {
        try {
            return $this->chain->parse($userAgent);
        } catch (Exception $exception) {
            $this->logger->error('Could not initialize user agent parser. Error: ' . $exception->getMessage(), [
                'user_agent' => $userAgent,
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine(),
            ]);

            return null;
        }
    }
}
