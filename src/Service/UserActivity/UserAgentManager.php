<?php

declare(strict_types=1);

namespace Unilend\Service\UserActivity;

use Exception;
use Psr\Log\LoggerInterface;
use Unilend\Entity\{Clients, UserAgent};
use Unilend\Repository\UserAgentRepository;
use UserAgentParser\Model\UserAgent as Model;
use UserAgentParser\Provider\Chain as UserAgentParser;

class UserAgentManager
{
    /** @var UserAgentParser */
    private $chain;
    /** @var LoggerInterface */
    private $logger;
    /** @var UserAgentRepository */
    private $userAgentRepository;

    /**
     * @param UserAgentParser     $chain
     * @param UserAgentRepository $userAgentRepository
     * @param LoggerInterface     $logger
     */
    public function __construct(UserAgentParser $chain, UserAgentRepository $userAgentRepository, LoggerInterface $logger)
    {
        $this->chain               = $chain;
        $this->logger              = $logger;
        $this->userAgentRepository = $userAgentRepository;
    }

    /**
     * @param Clients     $client
     * @param string|null $userAgent
     *
     *@throws Exception
     *
     * @return UserAgent|null
     */
    public function getClientUserAgent(Clients $client, string $userAgent): ?UserAgent
    {
        if (null === ($parsedUserAgent = $this->parse($userAgent))) {
            return null;
        }

        $browser = $parsedUserAgent->getBrowser();
        $device  = $parsedUserAgent->getDevice();

        $knownUserAgent = $this->userAgentRepository->findOneByClientAndBrowserAndDevice($client, $browser, $device);

        if (null !== $knownUserAgent) {
            return $knownUserAgent;
        }

        return (new UserAgent())
            ->setClient($client)
            ->setBrowserName($browser->getName())
            ->setBrowserVersion($browser->getVersion() ? $browser->getVersion()->getComplete() : null)
            ->setDeviceModel($device->getModel())
            ->setDeviceBrand($device->getBrand())
            ->setDeviceType($device->getType() ? mb_strtolower($device->getType()) : null)
            ->setUserAgentString($userAgent)
            ;
    }

    /**
     * @param string $userAgent
     *
     * @return Model|null
     */
    public function parse(string $userAgent): ?Model
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
