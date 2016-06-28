<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        /** @var AuthenticationUtils $authenticationUtils */
        $authenticationUtils = $this->get('security.authentication_utils');
        $error               = $authenticationUtils->getLastAuthenticationError();
        $lastUsername        = $authenticationUtils->getLastUsername();

        $pageData = [
            'last_username'  => $lastUsername,
            'error'          => $error
        ];

        if (false === is_null($request->getSession()->get('captchaInformation'))) {
            $pageData['captchaInformation'] = $request->getSession()->get('captchaInformation');
        }

        return $this->render('pages/login.html.twig',$pageData);
    }

    /**
     * @Route("login-check", name="login-check")
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
     * @Route("/captcha", name="show_captcha")
     */
    public function showCaptchaAction(Request $request)
    {
        $largeur  = 125;
        $hauteur  = 28;
        $longueur = 8;
        $liste    = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code     = '';

        $image = imagecreate($largeur, $hauteur) or die('Impossible d\'initializer GD');

        $img_create = imagecolorallocate($image, 214, 214, 214);
        imagecolortransparent($image, $img_create);

        for ($i = 0, $x = 0; $i < $longueur; $i++) {
            $charactere = substr($liste, rand(0, strlen($liste) - 1), 1);
            $x += 10 + mt_rand(0, 5);
            imagechar($image, mt_rand(3, 5), $x, mt_rand(5, 10), $charactere, imagecolorallocate($image, 183, 183, 183));
            $code .= strtolower($charactere);
        }

        ob_start();
        imagepng($image);
        $imageString = ob_get_clean();
        imagedestroy($image);

        $response = new Response($imageString);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Cache-Control', 'no-cache');

        $request->getSession()->set('captchaInformation/captchaCode', $code);

        return $response;
    }

}