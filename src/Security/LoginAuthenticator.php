<?php

namespace Unilend\Security;

use Doctrine\ORM\{EntityManagerInterface, OptimisticLockException};
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{Cookie, RedirectResponse, Request};
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\{EncoderAwareInterface, UserPasswordEncoderInterface};
use Symfony\Component\Security\Core\Exception\{AccountExpiredException, AuthenticationException, BadCredentialsException, CustomUserMessageAuthenticationException, DisabledException, LockedException, UsernameNotFoundException};
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\{UserInterface, UserProviderInterface};
use Symfony\Component\Security\Csrf\{CsrfToken, CsrfTokenManagerInterface};
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Unilend\Entity\{Clients, ClientsStatus, LoginLog, Settings};
use Unilend\Service\{CIPManager, GoogleRecaptchaManager, LenderManager};
use Unilend\Service\Front\LoginHistoryLogger;

class LoginAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    const COOKIE_NO_CF               = 'uld-nocf';
    const SESSION_NAME_LOGIN_CAPTCHA = 'displayLoginCaptcha';

    /** @var UserPasswordEncoderInterface */
    private $securityPasswordEncoder;
    /** @var RouterInterface */
    private $router;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var SessionAuthenticationStrategyInterface */
    private $sessionStrategy;
    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;
    /** @var GoogleRecaptchaManager */
    private $googleRecaptchaManager;
    /** @var LenderManager */
    private $lenderManager;
    /** @var CIPManager */
    private $cipManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var LoginHistoryLogger */
    private $loginHistoryLogger;

    /**
     * @param UserPasswordEncoderInterface           $securityPasswordEncoder
     * @param RouterInterface                        $router
     * @param EntityManagerInterface                 $entityManager
     * @param SessionAuthenticationStrategyInterface $sessionStrategy
     * @param CsrfTokenManagerInterface              $csrfTokenManager
     * @param GoogleRecaptchaManager                 $googleRecaptchaManager
     * @param LenderManager                          $lenderManager
     * @param CIPManager                             $cipManager
     * @param LoggerInterface                        $logger
     * @param LoginHistoryLogger                     $loginHistoryLogger
     */
    public function __construct(
        UserPasswordEncoderInterface $securityPasswordEncoder,
        RouterInterface $router,
        EntityManagerInterface $entityManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        CsrfTokenManagerInterface $csrfTokenManager,
        GoogleRecaptchaManager $googleRecaptchaManager,
        LenderManager $lenderManager,
        CIPManager $cipManager,
        LoggerInterface $logger,
        LoginHistoryLogger $loginHistoryLogger
    )
    {
        $this->securityPasswordEncoder = $securityPasswordEncoder;
        $this->router                  = $router;
        $this->entityManager           = $entityManager;
        $this->sessionStrategy         = $sessionStrategy;
        $this->csrfTokenManager        = $csrfTokenManager;
        $this->googleRecaptchaManager  = $googleRecaptchaManager;
        $this->lenderManager           = $lenderManager;
        $this->cipManager              = $cipManager;
        $this->logger                  = $logger;
        $this->loginHistoryLogger      = $loginHistoryLogger;
    }

    /**
     * @param Request       $request
     * @param UserInterface $user
     *
     * @return mixed|string
     */
    protected function getDefaultSuccessRedirectUrl(Request $request, UserInterface $user)
    {
        $targetPath = $request->get('_target_path');

        if ($targetPath) {
            $targetPath = $this->removeHost($targetPath);

            return $targetPath;
        }

        return $this->router->generate('demo_loans');

        // Borrower only
        if ([Clients::ROLE_BORROWER] === array_values(array_intersect($user->getRoles(), [Clients::ROLE_BORROWER, Clients::ROLE_PARTNER, Clients::ROLE_LENDER]))) {
            return $this->router->generate('collpub_loans');
        }

        if (in_array('ROLE_BORROWER', $user->getRoles())) {
            return $this->router->generate('borrower_account_projects');
        }

        if (in_array('ROLE_PARTNER', $user->getRoles())) {
            return $this->router->generate('partner_home');
        }

        return $this->router->generate('home');
    }

    /**
     * @inheritDoc
     */
    protected function getLoginUrl()
    {
        return $this->router->generate('login');
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(Request $request): array
    {
        $username       = $request->request->get('_username');
        $password       = $request->request->get('_password');
        $csrfToken      = $request->request->get('_csrf_token');
        $captchaCode    = $request->request->get(GoogleRecaptchaManager::FORM_FIELD_NAME);
        $displayCaptcha = $request->getSession()->get(self::SESSION_NAME_LOGIN_CAPTCHA, false);

        if (false === filter_var($username, FILTER_VALIDATE_EMAIL)) {
            throw new CustomUserMessageAuthenticationException('invalid-username-format');
        }

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return [
            'username'       => $username,
            'password'       => $password,
            'csrfToken'      => $csrfToken,
            'captchaCode'    => $captchaCode,
            'captchaDisplay' => $displayCaptcha
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            $user = $userProvider->loadUserByUsername($credentials['username']);
        } catch (UsernameNotFoundException $exception) {
            throw new CustomUserMessageAuthenticationException('login-unknown');
        }
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $plainPassword = $credentials['password'];

        if (false === $this->securityPasswordEncoder->isPasswordValid($user, $plainPassword)) {
            throw new CustomUserMessageAuthenticationException('wrong-password');
        }

        if (false === $this->isCaptchaValid($credentials)) {
            throw new CustomUserMessageAuthenticationException('wrong-captcha');
        }

        if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $credentials['csrfToken']))) {
            throw new CustomUserMessageAuthenticationException('wrong-security-token');
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $request->getSession()->remove(self::SESSION_NAME_LOGIN_CAPTCHA);

        /** @var Clients $client */
        $client = $token->getUser();

        // Force to use the default password encoder if it's legacy
        if ($client instanceof EncoderAwareInterface && Clients::PASSWORD_ENCODER_MD5 === $client->getEncoderName()) {
            $client->useDefaultEncoder();
            try {
                $client->setPassword($this->securityPasswordEncoder->encodePassword($client, $this->getCredentials($request)['password']));
            } catch (BadCredentialsException $exception) {
                // hack for the old password which cannot pass the security check in encodePassword()
                $client->setPassword(password_hash($this->getCredentials($request)['password'], PASSWORD_DEFAULT));
            }
            try {
                $this->entityManager->flush($client);
            } catch (OptimisticLockException $exception) {
                $this->logger->warning('Cannot save the re-encoded password. Error: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ]);
            }
        }

        $this->loginHistoryLogger->saveSuccessfulLogin($client, $request->getClientIp(), $request->headers->get('User-Agent'));
        $this->sessionStrategy->onAuthentication($request, $token);

        try {
            $needUpdatePersonalData = $this->lenderManager->needUpdatePersonalData($client);
            $needCipEvaluation      = $this->cipManager->needReevaluation($client);
        } catch (\InvalidArgumentException $exception) {
            $needUpdatePersonalData = false;
            $needCipEvaluation      = false;
        } catch (\Exception $exception) {
            $needUpdatePersonalData = false;
            $needCipEvaluation      = false;

            $this->logger->error('An error occurs when calling LenderManager::needUpdatePersonalData() Error : ' . $exception->getMessage(), [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'id_client' => $client->getIdClient(),
            ]);
        }

        if ($needUpdatePersonalData) {
            $targetPath = $this->router->generate('lender_data_update_start');
        } elseif ($needCipEvaluation) {
            $targetPath = $this->router->generate('cip_index');
        } else {
            $targetPath = $this->getUserSpecificTargetPath($request, $providerKey, $client);
        }

        $response = new RedirectResponse($targetPath);

        $cookie = new Cookie(self::COOKIE_NO_CF, 1);
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if (
            $exception instanceof LockedException
            || $exception instanceof DisabledException
            || $exception instanceof AccountExpiredException
        ) {
            $customException = new CustomUserMessageAuthenticationException('closed-account');
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $customException);
        }

        $previousFailures = 0;

        if (
            $exception instanceof CustomUserMessageAuthenticationException
            && in_array($exception->getMessage(), ['wrong-password', 'login-unknown', 'wrong-captcha', 'wrong-security-token'])
        ) {
            $failuresBeforeCaptcha = $this->entityManager
                ->getRepository(Settings::class)
                ->findOneBy(['type' => 'Echecs login avant affichage captcha'])
                ->getValue();
            $loginLogRepository    = $this->entityManager->getRepository(LoginLog::class);
            $previousFailures      = $loginLogRepository->countLastFailuresByIp($request->server->get('REMOTE_ADDR'), new \DateInterval('PT10M'));
            $displayCaptcha        = $previousFailures + 1 >= $failuresBeforeCaptcha;

            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set(self::SESSION_NAME_LOGIN_CAPTCHA, $displayCaptcha);
        }

        $loginLog = $this->loginHistoryLogger->saveFailureLogin($this->getCredentials($request)['username'], $request->getClientIp(), $exception->getMessage());

        if ('wrong-security-token' === $exception->getMessage()) {
            $this->logger->warning('Invalid CSRF token', [
                'login_log ID' => null !== $loginLog ? $loginLog->getIdLogLogin() : '',
                'server'       => exec('hostname'),
                'token'        => $this->getCredentials($request)['csrfToken'],
                'tries'        => $previousFailures,
                'REMOTE_ADDR'  => $request->server->get('REMOTE_ADDR')
            ]);
        }

        return new RedirectResponse($this->getLoginUrl());
    }

    /**
     * @param array $credentials
     *
     * @return bool
     */
    private function isCaptchaValid(array $credentials)
    {
        return true;

        if (isset($credentials['captchaDisplay']) && true === $credentials['captchaDisplay']) {
            if (false === isset($credentials['captchaCode'])) {
                return false;
            }

            return $this->googleRecaptchaManager->isValid($credentials['captchaCode']);
        }

        return true;
    }

    /**
     * Remove the host part from URL to avoid the external redirection
     *
     * @param $target
     *
     * @return string
     */
    private function removeHost($target)
    {
        // handle protocol-relative URLs that parse_url() doesn't like
        if (substr($target, 0, 2) === '//') {
            $target = 'proto:' . $target;
        }

        $parsedUrl = parse_url($target);
        $path      = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
        $query     = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment  = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return $path . $query . $fragment;
    }

    /**
     * @param Request $request
     * @param string  $providerKey
     * @param Clients $client
     *
     * @return string
     */
    private function getUserSpecificTargetPath(Request $request, string $providerKey, Clients $client): string
    {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if (! $targetPath) {
            $targetPath = $this->getDefaultSuccessRedirectUrl($request, $client);
        }

        if ($client->isLender()) {
            switch ($client->getIdClientStatusHistory()->getIdStatus()->getId()) {
                case ClientsStatus::STATUS_CREATION:
                    $targetPath = $this->router->generate('lender_subscription_documents', ['clientHash' => $client->getHash()]);
                    break;
                case ClientsStatus::STATUS_COMPLETENESS:
                case ClientsStatus::STATUS_COMPLETENESS_REMINDER:
                    $targetPath = $this->router->generate('lender_completeness');
                    break;
            }
        }

        return $targetPath;
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request): bool
    {
        return $request->getPathInfo() == '/login-check';
    }
}
