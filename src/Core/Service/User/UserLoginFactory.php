<?php

declare(strict_types=1);

namespace Unilend\Core\Service\User;

use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use Unilend\Core\Entity\{User, UserFailedLogin, UserSuccessfulLogin};
use Unilend\Core\Exception\Authentication\RecaptchaChallengeFailedException;
use Unilend\Core\Service\{UserActivity\IpGeoLocManager, UserActivity\UserAgentManager};

class UserLoginFactory
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
     * @param User   $user
     * @param string $action
     *
     * @return UserSuccessfulLogin

     **@throws Exception
     *
     */
    public function createUserLoginSuccess(User $user, string $action): UserSuccessfulLogin
    {
        $entry = new UserSuccessfulLogin($user, $action);

        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $ip = $request->getClientIp();
            $entry->setIp($ip);

            if ($request->headers->get('User-Agent')) {
                $userAgent = $this->userAgentManager->getUserUserAgent($user, $request->headers->get('User-Agent'));
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

        $recaptchaResult = $user->getRecaptchaResult();

        if ($recaptchaResult) {
            $entry->setRecaptchaScore($recaptchaResult->score);
        }

        return $entry;
    }

    /**
     * @param Throwable   $exception
     * @param string|null $username
     *
     * @return UserFailedLogin

     **@throws Exception
     *
     */
    public function createUserLoginFailure(Throwable $exception, ?string $username = null): UserFailedLogin
    {
        $failedLogin = new UserFailedLogin();

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
