<?php

declare(strict_types=1);

namespace Unilend\Service\User;

use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Unilend\Entity\{ClientFailedLogin, ClientSuccessfulLogin, Clients};
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
     * @throws Exception
     *
     * @return ClientSuccessfulLogin
     */
    public function createClientLoginSuccess(Clients $client, string $action): ClientSuccessfulLogin
    {
        $entry = new ClientSuccessfulLogin($client, $action);

        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $userAgent = $this->userAgentManager->getClientUserAgent($client, $request->headers->get('User-Agent'));
            $ip        = $request->getClientIp();

            $entry->setUserAgent($userAgent)
                ->setIp($ip)
            ;

            if ($ip) {
                $this->ipGeoLocManager->setClientLoginLocation($entry, $ip);
            }
        }

        return $entry;
    }

    /**
     * @throws Exception
     *
     * @return ClientFailedLogin
     */
    public function createClientLoginFailure(): ClientFailedLogin
    {
        $failure = new ClientFailedLogin();

        if ($request = $this->requestStack->getCurrentRequest()) {
            $failure->setIp($request->getClientIp());
        }

        return $failure;
    }
}
