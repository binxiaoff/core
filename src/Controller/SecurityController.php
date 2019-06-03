<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Unilend\Security\LoginAuthenticator;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     *
     * @param Request             $request
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return RedirectResponse|Response
     */
    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('home');
        }

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $pageData     = [
            'last_username'       => $lastUsername,
            'error'               => $error,
            'recaptchaKey'        => getenv('GOOGLE_RECAPTCHA_KEY'),
            'displayLoginCaptcha' => $request->getSession()->get(LoginAuthenticator::SESSION_NAME_LOGIN_CAPTCHA, false),
        ];

        return $this->render('security/login.html.twig', $pageData);
    }

    /**
     * @Route("/login-check", name="login-check")
     */
    public function loginCheckAction()
    {
        /*
        This method will never be executed.
        When we submit, Symfony’s Guard security system intercepts the request and processes the login information.
        This works as long as we POST username and password as defined in LoginAuthenticator::getCredentials().
        */
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        /*
        Just like with the loginCheckAction, the code here won’t actually get hit.
        Instead, Symfony intercepts the request and processes the logout for us.
        */
    }

    /**
     * @Route("/security/recaptcha", name="security_recaptcha", condition="request.isXmlHttpRequest()")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function recaptchaAction(Request $request): JsonResponse
    {
        return $this->json($request->getSession()->get(LoginAuthenticator::SESSION_NAME_LOGIN_CAPTCHA, false));
    }
}
