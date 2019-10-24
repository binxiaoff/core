<?php

declare(strict_types=1);

namespace Unilend\Service\User;

use Symfony\Component\HttpFoundation\RequestStack;
use Unilend\Entity\{ClientLogin, Clients};
use Unilend\Service\{UserActivity\IpGeoLocManager, UserActivity\UserAgentManager};

class ClientLoginFactory
{
    /** @var IpGeoLocManager */
    private $ipGeoLocManager;
    /** @var UserAgentManager */
    private $userAgentManager;
    /** @var RequestStack */
    private $requestStack;

    /**
     * @param IpGeoLocManager  $ipGeoLocManager
     * @param UserAgentManager $userAgentManager
     * @param RequestStack     $requestStack
     */
    public function __construct(
        IpGeoLocManager $ipGeoLocManager,
        UserAgentManager $userAgentManager,
        RequestStack $requestStack
    ) {
        $this->ipGeoLocManager  = $ipGeoLocManager;
        $this->userAgentManager = $userAgentManager;
        $this->requestStack     = $requestStack;
    }

    /**
     * @param Clients $client
     * @param string  $action
     *
     * @return ClientLogin
     */
    public function createClientLoginEntry(Clients $client, string $action): ClientLogin
    {
        $entry = (new ClientLogin($client, $action));

        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $userAgent = $this->userAgentManager->parse($request->headers->get('User-Agent'));
            $ip        = $request->getClientIp();

            $entry->setUserAgentHistory($userAgent)
                ->setIp($ip)
            ;

            if ($ip) {
                $this->ipGeoLocManager->setClientLoginLocation($entry, $ip);
            }
        }

        return $entry;
    }
}
