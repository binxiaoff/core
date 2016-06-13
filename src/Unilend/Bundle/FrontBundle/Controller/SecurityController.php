<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     * @param Request $request
     */
    public function loginAction(Request $request)
    {
        /** @var AuthenticationUtils $authenticationUtils */
        $authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();
        //var_dump($_SESSION);
        //var_dump($this->container->get('session'));

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'UnilendFrontBundle:pages:connexion_inscription.html.twig',
            array(
                'last_username' => $lastUsername,
                'error' => $error
            )
        );
    }

    /**
     * @Route("login-check", name="login-check")
     */
    public function loginCheckAction()
    {
        /*
        This method will never be executed.
        When we submit, Symfony’s security system intercepts the request and processes the login information.
        This works as long as we POST username and password as defined in security.yml to the URL /login_check.
        This URL is special because its route is configured as the check_path in security.yml.
        The loginCheckAction method is never executed, because Symfony intercepts POST requests to that URL.
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



}