<?php

namespace Unilend\Security;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{Cookie, RedirectResponse, Request};
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\{AccountExpiredException, AuthenticationException, CustomUserMessageAuthenticationException, DisabledException, LockedException, UsernameNotFoundException};
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\{UserInterface, UserProviderInterface};
use Symfony\Component\Security\Csrf\{CsrfToken, CsrfTokenManagerInterface};
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Unilend\Entity\{Clients, ClientsStatus, LoginLog, Settings};
use Unilend\Service\Front\LoginHistoryLogger;
use Unilend\Service\{CIPManager, GoogleRecaptchaManager, LenderManager};

class LoginAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    public const COOKIE_NO_CF               = 'uld-nocf';
    public const SESSION_NAME_LOGIN_CAPTCHA = 'displayLoginCaptcha';

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
    ) {
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
     * {@inheritdoc}
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
            'captchaDisplay' => $displayCaptcha,
        ];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $request->getSession()->remove(self::SESSION_NAME_LOGIN_CAPTCHA);

        /** @var Clients $client */
        $client = $token->getUser();

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
     * {@inheritdoc}
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
                ->getValue()
            ;
            $loginLogRepository = $this->entityManager->getRepository(LoginLog::class);
            $previousFailures   = $loginLogRepository->countLastFailuresByIp($request->server->get('REMOTE_ADDR'), new \DateInterval('PT10M'));
            $displayCaptcha     = $previousFailures + 1 >= $failuresBeforeCaptcha;

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
                'REMOTE_ADDR'  => $request->server->get('REMOTE_ADDR'),
            ]);
        }

        return new RedirectResponse($this->getLoginUrl());
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        return '/login-check' === $request->getPathInfo();
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getDefaultSuccessRedirectUrl(Request $request): string
    {
        $targetPath = $request->get('_target_path');

        if ($targetPath) {
            return $this->removeHost($targetPath);
        }

        return $this->router->generate('wallet');
    }

    /**
     * {@inheritdoc}
     */
    protected function getLoginUrl()
    {
        return $this->router->generate('login');
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
     * Remove the host part from URL to avoid the external redirection.
     *
     * @param mixed $target
     *
     * @return string
     */
    private function removeHost($target)
    {
        // handle protocol-relative URLs that parse_url() doesn't like
        if ('//' === mb_substr($target, 0, 2)) {
            $target = 'proto:' . $target;
        }

        $parsedUrl = parse_url($target);
        $path      = $parsedUrl['path'] ?? '/';
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

        if (!$targetPath) {
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
}
