<?php

declare(strict_types=1);

namespace Unilend\Core\Service\UserActivity;

use Exception;
use Psr\Log\LoggerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\UserAgent;
use Unilend\Core\Repository\UserAgentRepository;
use WhichBrowser\Parser;

class UserAgentManager
{
    /** @var LoggerInterface */
    private $logger;

    /** @var UserAgentRepository */
    private $userAgentRepository;

    /**
     * UserAgentManager constructor.
     */
    public function __construct(UserAgentRepository $userAgentRepository, LoggerInterface $logger)
    {
        $this->logger              = $logger;
        $this->userAgentRepository = $userAgentRepository;
    }

    public function getUserUserAgent(User $user, string $userAgent): ?UserAgent
    {
        if (null === ($parsedUserAgent = $this->parse($userAgent))) {
            return null;
        }

        $browser        = $parsedUserAgent->browser;
        $device         = $parsedUserAgent->device;
        $knownUserAgent = $this->userAgentRepository->findOneByUserAndBrowserAndDevice($user, $browser, $device);

        if (null !== $knownUserAgent) {
            return $knownUserAgent;
        }

        return (new UserAgent())
            ->setUser($user)
            ->setBrowserName($browser->name)
            ->setBrowserVersion($browser->version ? $browser->version->toString() : null)
            ->setDeviceModel($device->model)
            ->setDeviceBrand($device->getManufacturer() ?: null)
            ->setDeviceType($device->type ? \mb_strtolower($device->type) : null)
            ->setUserAgentString($userAgent)
            ;
    }

    public function parse(string $userAgent): ?Parser
    {
        try {
            return new Parser(['User-Agent' => $userAgent]);
        } catch (Exception $exception) {
            $this->logger->warning('Could not initialize user agent parser. Error: ' . $exception->getMessage(), [
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
