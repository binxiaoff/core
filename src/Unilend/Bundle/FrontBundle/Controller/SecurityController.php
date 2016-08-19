<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Unilend\core\Loader;

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

    /**
     * @Route("/pwd", name="pwd_forgotten")
     * @Method("POST")
     */
    public function passwordForgottenAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {

            /** @var \clients $clients */
            $clients = $this->get('unilend.service.entity_manager')->getRepository('clients');
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');
            /** @var \settings $settings */
            $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');

            $post = $request->request->all();

            if (isset($post['email']) && $ficelle->isEmail($post['email']) && $clients->get($post['email'], 'email')) {
                $settings->get('Facebook', 'type');
                $lien_fb = $settings->value;
                $settings->get('Twitter', 'type');
                $lien_tw = $settings->value;

                $varMail = [
                    'surl'          => $this->get('assets.packages')->getUrl(''),
                    'url'           => $this->get('assets.packages')->getUrl(''),
                    'prenom'        => $clients->prenom,
                    'login'         => $clients->email,
                    'link_password' => $this->generateUrl('define_new_password', ['clientHash' => $clients->hash]),
                    'lien_fb'       => $lien_fb,
                    'lien_tw'       => $lien_tw
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('mot-de-passe-oublie', $varMail);
                $message->setTo($clients->email);
                $mailer = $this->get('mailer');
                $mailer->send($message);

                return new JsonResponse('ok');
            } else {
                return new JsonResponse('nok');
            }
        } else {
            return new Response('not an ajax request');
        }
    }

    /**
     * @Route("/nouveau-mot-de-passe/{clientHash}", name="define_new_password")
     */
    public function defineNewPasswordAction($clientHash, Request $request)
    {
        return 'ici viendra la page de nouveau mot de passe';
    }

}