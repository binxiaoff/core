<?php
namespace Unilend\Bundle\FrontBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;

class LoginAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    /** @var UserPasswordEncoder */
    private $securityPasswordEncoder;
    /** @var RouterInterface */
    private $router;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(UserPasswordEncoder $securityPasswordEncoder, RouterInterface $router, EntityManager $entityManager)
    {
        $this->securityPasswordEncoder = $securityPasswordEncoder;
        $this->router                  = $router;
        $this->entityManager           = $entityManager;
    }

    protected function getDefaultSuccessRedirectUrl(Request $request, UserInterface $user)
    {
        $targetPath = $request->get('_target_path');

        if ($targetPath) {
            return $targetPath;
        }

        if (in_array('ROLE_LENDER', $user->getRoles())) {
            return $this->router->generate('lender_dashboard');
        }

        if (in_array('ROLE_BORROWER', $user->getRoles())) {
            return $this->router->generate('borrower_account_projects');
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

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return [
            'username'           => $username,
            'password'           => $password,
            'captcha'            => $captcha,
            'captchaInformation' => $captchaInformation
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

        if (password_needs_rehash($user->getPassword(), PASSWORD_BCRYPT)) {
            /** @var \clients $client */
            $client = $this->entityManager->getRepository('clients');
            $client->get($user->getClientId());
            $client->password = password_hash($this->getCredentials($request)['password'], PASSWORD_BCRYPT);
            $client->update();
        }

        if ($user instanceof UserInterface && in_array('ROLE_LENDER', $user->getRoles())) {
            if ($user->getSubscriptionStep() < 3) {
                //TODO uncomment once route created
                //return new RedirectResponse($this->router->generate('lender_subscription'));
            }

            if (in_array($user->getClientStatus(), [\clients_status::COMPLETENESS, \clients_status::COMPLETENESS_REMINDER])) {
                //TODO uncomment once route created
                //return new RedirectResponse($this->router->generate('lender_completeness'));
            }

            if (false === $user->hasAcceptedCurrentTerms()) {
                //TODO add  message about Terms
            }
        }

        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if (!$targetPath) {
            $targetPath = $this->getDefaultSuccessRedirectUrl($request, $user);
        }

        return new RedirectResponse($targetPath);
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($exception instanceof LockedException || $exception instanceof DisabledException || $exception instanceof AccountExpiredException) {
            $customException = new CustomUserMessageAuthenticationException('closed-account');
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $customException);
        }

        if ($exception instanceof CustomUserMessageAuthenticationException && in_array($exception->getMessage(), ['wrong-password', 'login-unknown'])) {
            $oNowMinusTenMinutes = new \datetime('NOW - 10 minutes');

            /** @var \login_log $loginLog */
            $loginLog        = $this->entityManager->getRepository('login_log');
            $iPreviousTries  = $loginLog->counter('IP = "' . $_SERVER["REMOTE_ADDR"] . '" AND date_action >= "' . $oNowMinusTenMinutes->format('Y-m-d H:i:s') . '" AND statut = 0');
            $iWaitingPeriod  = 0;
            $iPreviousResult = 1;

            if ($iPreviousTries > 0 && $iPreviousTries < 1000) { // 1000 pour ne pas bloquer le site
                for ($i = 1; $i <= $iPreviousTries; $i++) {
                    $iWaitingPeriod  = $iPreviousResult * 2;
                    $iPreviousResult = $iWaitingPeriod;
                }
            }

            $aCaptchaInformation = [
                'waitingPeriod'        => $iWaitingPeriod,
                'displayWaitingPeriod' => ($iPreviousTries > 1) ? true : false,
                'displayCaptcha'       => ($iPreviousTries > 5) ? true : false
            ];

            $request->getSession()->set('captchaInformation', $aCaptchaInformation);
        }

        return new RedirectResponse($this->getLoginUrl());
    }

    private function checkCaptcha($credentials)
    {
        if (isset($credentials['captchaInformation']['captchaCode']) && isset($credentials['captcha'])) {
            return $credentials['captchaInformation']['captchaCode'] == strtolower($credentials['captcha']);
        }

        return true;
    }
}
