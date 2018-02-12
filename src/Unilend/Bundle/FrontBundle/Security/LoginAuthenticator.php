<?php

namespace Unilend\Bundle\FrontBundle\Security;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\LoginLog;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

class LoginAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    const COOKIE_NO_CF = 'uld-nocf';

    /** @var UserPasswordEncoder */
    private $securityPasswordEncoder;
    /** @var RouterInterface */
    private $router;
    /** @var EntityManager */
    private $entityManager;
    /** @var SessionAuthenticationStrategyInterface */
    private $sessionStrategy;
    /** @var CsrfTokenManagerInterface */
    private $csrfTokenManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param UserPasswordEncoder                    $securityPasswordEncoder
     * @param RouterInterface                        $router
     * @param EntityManager                          $entityManager
     * @param SessionAuthenticationStrategyInterface $sessionStrategy
     * @param CsrfTokenManagerInterface              $csrfTokenManager
     * @param LoggerInterface                        $logger
     */
    public function __construct(
        UserPasswordEncoder $securityPasswordEncoder,
        RouterInterface $router,
        EntityManager $entityManager,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerInterface $logger
    )
    {
        $this->securityPasswordEncoder = $securityPasswordEncoder;
        $this->router                  = $router;
        $this->entityManager           = $entityManager;
        $this->sessionStrategy         = $sessionStrategy;
        $this->csrfTokenManager        = $csrfTokenManager;
        $this->logger                  = $logger;
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

        if (in_array('ROLE_LENDER', $user->getRoles())) {
            return $this->router->generate('lender_dashboard');
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
    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() != '/login-check') {
            return null;
        }

        $username           = $request->request->get('_username');
        $password           = $request->request->get('_password');
        $captcha            = $request->request->get('captcha');
        $captchaInformation = $request->getSession()->get('captchaInformation');
        $csrfToken          = $request->get('_csrf_token');

        if (false === filter_var($username, FILTER_VALIDATE_EMAIL)) {
            throw new CustomUserMessageAuthenticationException('invalid-username-format');
        }

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return [
            'username'           => $username,
            'password'           => $password,
            'captcha'            => $captcha,
            'captchaInformation' => $captchaInformation,
            'csrfToken'          => $csrfToken
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            $username = $userProvider->loadUserByUsername($credentials['username']);
        } catch (UsernameNotFoundException $exception) {
            throw new CustomUserMessageAuthenticationException('login-unknown');
        }
        return $username;
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

        if (false === $this->checkCaptcha($credentials)) {
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
        /** @var BaseUser $user */
        $user = $token->getUser();
        $request->getSession()->remove('captchaInformation');
        /** @var Clients $client */
        $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($user->getClientId());

        // Update the password encoder if it's legacy
        if ($user instanceof EncoderAwareInterface && (null !== $encoderName = $user->getEncoderName())) {
            $user->useDefaultEncoder(); // force to use the default password encoder
            try {
                $client->setPassword($this->securityPasswordEncoder->encodePassword($user, $this->getCredentials($request)['password']));
            } catch (BadCredentialsException $exeption) {
                $client->setPassword(password_hash($this->getCredentials($request)['password'], PASSWORD_DEFAULT)); // hack for the old password which cannot pass the security check in encodePassword()
            }
            $this->entityManager->flush($client);
        }

        $this->saveLogin($client);
        $this->sessionStrategy->onAuthentication($request, $token);

        $targetPath = $this->getUserSpecificTargetPath($request, $providerKey, $user);
        $response   = new RedirectResponse($targetPath);

        $cookie = new Cookie(self::COOKIE_NO_CF, 1);
        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // @todo supprimer traductions

        if (
            $exception instanceof LockedException
            || $exception instanceof DisabledException
            || $exception instanceof AccountExpiredException
        ) {
            $customException = new CustomUserMessageAuthenticationException('closed-account');
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $customException);
        }

        if ($exception instanceof CustomUserMessageAuthenticationException && in_array($exception->getMessage(), ['wrong-password', 'login-unknown', 'wrong captcha', 'wrong-security-token'])) {
            $loginLogRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:LoginLog');
            $previousFailures   = $loginLogRepository->countLastFailuresByIp($request->server->get('REMOTE_ADDR'), new \DateInterval('PT10M'));

            $iWaitingPeriod      = 0;
            $iPreviousResult     = 1;

            if ($previousFailures > 0 && $previousFailures < 1000) { // 1000 pour ne pas bloquer le site
                for ($i = 1; $i <= $previousFailures; $i++) {
                    $iWaitingPeriod  = $iPreviousResult * 2;
                    $iPreviousResult = $iWaitingPeriod;
                }
            }

            $aCaptchaInformation = [
                'waitingPeriod'        => $iWaitingPeriod,
                'displayWaitingPeriod' => $previousFailures > 1,
                'displayCaptcha'       => $previousFailures > 5
            ];

            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set('captchaInformation', $aCaptchaInformation);
        }

        $loginLog = new LoginLog();
        $loginLog->setPseudo($this->getCredentials($request)['username']);
        $loginLog->setIp($request->getClientIp());
        $loginLog->setRetour($exception->getMessage());

        $this->entityManager->persist($loginLog);
        $this->entityManager->flush($loginLog);

        if ('wrong-security-token' === $exception->getMessage()) {
            $this->logger->warning('Invalid CSRF token', [
                'login_log ID' => $loginLog->getIdLogLogin(),
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
    private function checkCaptcha(array $credentials)
    {
        if (isset($credentials['captchaInformation']['captchaCode']) && isset($credentials['captcha'])) {
            return $credentials['captchaInformation']['captchaCode'] == strtolower($credentials['captcha']);
        }

        return true;
    }

    /**
     * @param Clients $client
     */
    private function saveLogin(Clients $client)
    {
        try {
            $client->setLastlogin(new \DateTime('NOW'));

            $isLender   = $client->isLender();
            $isBorrower = $client->isBorrower();
            $isPartner  = $client->isPartner();

            if ($isLender && $isBorrower) {
                $type = ClientsHistory::TYPE_CLIENT_LENDER_BORROWER;
            } elseif ($isLender) {
                $type = ClientsHistory::TYPE_CLIENT_LENDER;
            } elseif ($isBorrower) {
                $type = ClientsHistory::TYPE_CLIENT_BORROWER;
            } elseif ($isPartner) {
                $type = ClientsHistory::TYPE_CLIENT_PARTNER;
            }

            $clientHistory = new ClientsHistory();
            $clientHistory->setIdClient($client);
            $clientHistory->setType($type);
            $clientHistory->setStatus(ClientsHistory::STATUS_ACTION_LOGIN);

            $this->entityManager->persist($clientHistory);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            $this->logger->error(
                'An error occurred while logging user login: ' . $exception->getMessage(),
                ['id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }
    }

    /**
     * Remove the host part from URL to avoid the external redirection
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
     * @param Request  $request
     * @param string   $providerKey
     * @param BaseUser $user
     *
     * @return string
     */
    private function getUserSpecificTargetPath(Request $request, string $providerKey, BaseUser $user) : string
    {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if (! $targetPath) {
            $targetPath = $this->getDefaultSuccessRedirectUrl($request, $user);
        }

        if (
            $user instanceof UserLender
            && in_array($user->getClientStatus(), [\clients_status::COMPLETENESS, \clients_status::COMPLETENESS_REMINDER])
        ) {
            $targetPath = $this->router->generate('lender_completeness');
        }

        return $targetPath;
    }
}
