<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\UserActivity;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, UserAgent as UserAgentEntity};
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
     * @return UserAgentEntity|null
     * @throws \Exception
     */
    public function saveClientUserAgent(Clients $client, ?string $userAgent): ?UserAgentEntity
    {
        if ($parser = $this->parse($userAgent)) {
            $browser = $parser->getBrowser();
            $device  = $parser->getDevice();

            $knownUserAgent = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UserAgent')
                ->findOneBy(['idClient' => $client, 'browserName' => $browser->getName(), 'deviceModel' => $device->getModel(), 'deviceBrand' => $device->getBrand(), 'deviceType' => $device->getType()]);

            if ($knownUserAgent) {
                return $knownUserAgent;
            } else {
                $userAgent = new UserAgentEntity();
                $userAgent
                    ->setIdClient($client)
                    ->setBrowserName($browser->getName())
                    ->setBrowserVersion($browser->getVersion()->getComplete())
                    ->setDeviceModel($device->getModel())
                    ->setDeviceBrand($device->getBrand())
                    ->setDeviceType(strtolower($device->getType()))
                    ->setUserAgentString($userAgent);

                $this->entityManager->persist($userAgent);
                $this->entityManager->flush($userAgent);

                return $userAgent;
            }
        }

        return null;
    }

    /**
     * @param string|null $userAgent
     *
     * @return UserAgent|null
     */
    private function parse(?string $userAgent = null): ?UserAgent
    {
        try {
            return $this->chain->parse($userAgent);
        } catch (\Exception $exception) {
            $this->logger->error('Could not initialize user agent parser. Error: ' . $exception->getMessage(), [
                'user_agent' => $userAgent,
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine()
            ]);

            return null;
        }
    }
}
