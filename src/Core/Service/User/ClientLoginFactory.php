<?php

declare(strict_types=1);

namespace Unilend\Core\Service\User;

use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use Unilend\Core\Entity\ClientFailedLogin;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\{ClientSuccessfulLogin};
use Unilend\Core\Exception\Authentication\RecaptchaChallengeFailedException;
use Unilend\Core\Service\{UserActivity\IpGeoLocManager, UserActivity\UserAgentManager};

class ClientLoginFactory
{
    /** @var IpGeoLocManager */
    private IpGeoLocManager $ipGeoLocManager;
    /** @var UserAgentManager */
    private UserAgentManager $userAgentManager;
    /** @var RequestStack */
    private RequestStack $requestStack;

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
            $ip = $request->getClientIp();
            $entry->setIp($ip);

            if ($request->headers->get('User-Agent')) {
                $userAgent = $this->userAgentManager->getClientUserAgent($client, $request->headers->get('User-Agent'));
                $entry->setUserAgent($userAgent);
            }

            if ($ip) {
                $geoIp = $this->ipGeoLocManager->getGeoIpRecord($ip);

                if (null !== $geoIp) {
                    $entry->setCountryIsoCode($geoIp->country->isoCode);
                    $entry->setCity($geoIp->city->name);
                }
            }
        }

        $recaptchaResult = $client->getRecaptchaResult();

        if ($recaptchaResult) {
            $entry->setRecaptchaScore($recaptchaResult->score);
        }

        return $entry;
    }

    /**
     * @param Throwable   $exception
     * @param string|null $username
     *
     * @throws Exception
     *
     * @return ClientFailedLogin
     */
    public function createClientLoginFailure(Throwable $exception, ?string $username = null): ClientFailedLogin
    {
        $failedLogin = new ClientFailedLogin();

        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $failedLogin->setIp($request->getClientIp());
        }

        $failedLogin->setUsername($username);
        $failedLogin->setError($exception->getMessage());

        if ($exception instanceof RecaptchaChallengeFailedException) {
            $failedLogin->setRecaptchaScore($exception->getResult()->score);
        }

        return $failedLogin;
    }
}
