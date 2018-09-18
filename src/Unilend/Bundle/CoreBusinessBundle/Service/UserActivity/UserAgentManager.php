<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\UserActivity;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\UserAgent as UserAgentEntity;
use UserAgentParser\Model\UserAgent;
use UserAgentParser\Provider\Chain;

class UserAgentManager
{
    /** @var Chain */
    private $chain;
    /** @var RequestStack */
    private $requestStack;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * UserAgentManager constructor.
     *
     * @param Chain                  $chain
     * @param RequestStack           $requestStack
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(Chain $chain, RequestStack $requestStack, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->chain         = $chain;
        $this->requestStack  = $requestStack;
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @return string|null
     */
    public function getBrowserName(): ?string
    {
        if ($parser = $this->getParser()) {
            return $parser->getBrowser()->getName();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getDeviceModel(): ?string
    {
        if ($parser = $this->getParser()) {
            return $parser->getDevice()->getModel();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getDeviceBrand(): ?string
    {
        if ($parser = $this->getParser()) {
            return $parser->getDevice()->getBrand();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getDeviceType(): ?string
    {
        if ($parser = $this->getParser()) {
            return $parser->getDevice()->getType();
        }

        return null;
    }

    /**
     * @return string|string
     */
    public function getOperatingSystem(): ?string
    {
        if ($parser = $this->getParser()) {
            return $parser->getOperatingSystem()->getName();
        }

        return null;
    }

    /**
     * @param Clients     $client
     * @param string|null $userAgent
     *
     * @return UserAgentEntity|null
     */
    public function saveClientUserAgent(Clients $client, ?string $userAgent = null): ?UserAgentEntity
    {
        if ($parser = $this->getParser($userAgent)) {
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
                    ->setUserAgentString($this->getUserAgent());

                $this->entityManager->persist($userAgent);
                $this->entityManager->flush($userAgent);

                return $userAgent;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    private function getUserAgent(): string
    {
        if (null !== $this->requestStack && null !== $this->requestStack->getCurrentRequest()) {
            return $this->requestStack->getCurrentRequest()->headers->get('User-Agent');
        } else {
            return '';
        }
    }

    /**
     * @param string|null $userAgent
     *
     * @return UserAgent|null
     */
    private function getParser(?string $userAgent = null): ?UserAgent
    {
        if (null === $userAgent) {
            $userAgent = $this->getUserAgent();
        }

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
