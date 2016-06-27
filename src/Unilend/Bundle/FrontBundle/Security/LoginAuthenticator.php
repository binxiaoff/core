<?php

namespace Unilend\Bundle\FrontBundle\Security;


use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;


class LoginAuthenticator extends AbstractFormLoginAuthenticator
{
    /** @var UserPasswordEncoder  */
    private $securityPasswordEncoder;
    /** @var Router  */
    private $router;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(UserPasswordEncoder $securityPasswordEncoder, Router $router, EntityManager $entityManager)
    {
        $this->securityPasswordEncoder = $securityPasswordEncoder;
        $this->router                  = $router;
        $this->entityManager           = $entityManager;
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
        if ($request->getPathInfo() != '/login-check'){
            return null;
        }

        $username = $request->request->get('_username');
        $password           = $request->request->get('_password');
        $captcha            = $request->request->get('captcha');
        $captchaInformation = $request->getSession()->get('captchaInformation');

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return array(
            'username'           => $username,
            'password'           => $password,
            'captcha'            => $captcha,
            'captchaInformation' => $captchaInformation
        );
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

        if (false === $this->securityPasswordEncoder->isPasswordValid($user, $plainPassword)){
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
        $user  = $token->getUser();
        $request->getSession()->remove('captchaInformation');

        if ($user instanceof UserLender){
            if ($user->getSubscriptionStep() < 3) {
                //TODO uncomment once route created
                //return new RedirectResponse($this->router->generate('lender_subscription'));
            }

            if (in_array($user->getClientStatus(), array(\clients_status::COMPLETENESS, \clients_status::COMPLETENESS_REMINDER))) {
                //TODO uncomment once route created
                //return new RedirectResponse($this->router->generate('lender_completeness'));
            }

            if (false === $user->getHasAcceptedCurrentTerms()) {
                //TODO add  message about Terms
            }

            if ($request->getSession()->get('_security.default.target_path')) {
                return new RedirectResponse($request->getSession()->get('_security.default.target_path'));
            }

            return new RedirectResponse($this->router->generate('home'));

        }

        if ($user instanceof UserBorrower){
            return new RedirectResponse($this->router->generate('borrower_account'));
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {

        if ($exception instanceof LockedException || $exception instanceof DisabledException || $exception instanceof AccountExpiredException) {
            $customException =  new CustomUserMessageAuthenticationException('closed-account');
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $customException);
        }

        if ($exception instanceof CustomUserMessageAuthenticationException && in_array($exception->getMessage(), array('wrong-password', 'login-unknown'))) {
            $oNowMinusTenMinutes           = new \datetime('NOW - 10 minutes');

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
        return $credentials['captchaInformation']['captchaCode'] == strtolower($credentials['captcha']);
    }

}
