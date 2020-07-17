<?php

declare(strict_types=1);

namespace Unilend\Controller\JWT;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Unilend\Security\UsernamePasswordRecaptchaAuthenticator;

class Generate
{
    private UsernamePasswordRecaptchaAuthenticator $authenticator;

    private UserProviderInterface $userProvider;

    /**
     * @param UsernamePasswordRecaptchaAuthenticator $authenticator
     * @param UserProviderInterface                  $userProvider
     */
    public function __construct(UsernamePasswordRecaptchaAuthenticator $authenticator, UserProviderInterface $userProvider)
    {
        $this->authenticator = $authenticator;
        $this->userProvider  = $userProvider;
    }

    /**
     * @param Request $request
     *
     * @return Response|null
     */
    public function __invoke(Request $request)
    {
        $response = $this->authenticator->start($request);

        if ($this->authenticator->supports($request)) {
            $isValid = false;

            $credentials = $this->authenticator->getCredentials($request);
            $user        = $this->authenticator->getUser($credentials, $this->userProvider);

            if ($user) {
                try {
                    $isValid = $this->authenticator->checkCredentials($credentials, $user);
                } catch (AuthenticationException $exception) {
                    $response = $this->authenticator->onAuthenticationFailure($request, $exception);
                }
            }

            if ($isValid) {
                $token    = $this->authenticator->createAuthenticatedToken($user, 'api');
                $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'api');
            }
        }

        return $response;
    }
}
