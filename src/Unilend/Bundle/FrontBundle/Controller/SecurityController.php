<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\FrontBundle\Security\BCryptPasswordEncoder;
use Unilend\core\Loader;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        if ($this->getUser()) {
            $tokenStorage = $this->get('security.token_storage');
            return $this->get('unilend.frontbundle.security.login_authenticator')->onAuthenticationSuccess($request, $tokenStorage->getToken(), 'default');
        }

        /** @var AuthenticationUtils $authenticationUtils */
        $authenticationUtils = $this->get('security.authentication_utils');
        $error               = $authenticationUtils->getLastAuthenticationError();
        $lastUsername        = $authenticationUtils->getLastUsername();

        $pageData = [
            'last_username'  => $lastUsername,
            'error'          => $error
        ];

        $pageData['captchaInformation'] = $request->getSession()->get('captchaInformation', []);
        $request->getSession()->remove('captchaInformation');

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

            if (isset($post['client_email']) && $ficelle->isEmail($post['client_email']) && $clients->get($post['client_email'], 'email')) {
                $settings->get('Facebook', 'type');
                $lien_fb = $settings->value;
                $settings->get('Twitter', 'type');
                $lien_tw = $settings->value;

                $varMail = [
                    'surl'          => $this->get('assets.packages')->getUrl(''),
                    'url'           => $this->get('assets.packages')->getUrl(''),
                    'prenom'        => $clients->prenom,
                    'login'         => $clients->email,
                    'link_password' => $this->generateUrl('define_new_password', ['clientHash' => $clients->hash], UrlGeneratorInterface::ABSOLUTE_URL),
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
    public function defineNewPasswordAction($clientHash)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientHash, 'hash');

        return $this->render('pages/password_forgotten.html.twig', ['clientHash' => $client->hash, 'secretQuestion' => $client->secrete_question]);
    }

    /**
     * @Route("/nouveau-mot-de-passe/submit/{clientHash}", name="save_new_password")
     * @Method("POST")
     */
    public function changePasswordFormAction($clientHash, Request $request)
    {
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientHash, 'hash');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var UserPasswordEncoder $securityPasswordEncoder */
        $securityPasswordEncoder = $this->get('security.password_encoder');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $post = $request->request->all();

        if (empty($post['client_new_password'])) {
            $this->addFlash('passwordErrors', $translator->trans('common-validators_missing-new-password'));
        }
        if (empty($post['client_new_password_confirmation'])) {
            $this->addFlash('passwordErrors', $translator->trans('common-validators_missing-new-password-confirmation'));
        }
        if (empty($post['client_secret_answer']) || md5($post['client_secret_answer']) !== $client->secrete_reponse) {
            $this->addFlash('passwordErrors', $translator->trans('common-validators_secret-answer-invalid'));
        }

        if (false === empty($post['client_new_password']) && false === empty($post['client_new_password_confirmation']) && false === empty($post['client_secret_answer'])) {
            if ($post['client_new_password'] !== $post['client_new_password_confirmation']){
                $this->addFlash('passwordErrors', $translator->trans('common-validators_password-not-equal'));
            }
            if (false === $ficelle->password_fo($post['client_new_password'], 6)) {
                $this->addFlash('passwordErrors', $translator->trans('common-validators_password-invalid'));
            }
        }

        if (false === $this->get('session')->getFlashBag()->has('passwordErrors')){
            $user = $this->get('unilend.frontbundle.security.user_provider')->loadUserByUsername($client->email);
            $password = $securityPasswordEncoder->encodePassword($user, $post['client_new_password']);
            $client->password = $password;
            $client->update();
            $this->sendPasswordModificationEmail($client);
            $this->addFlash('passwordSuccess', $translator->trans('password-forgotten_new-password-form-success-message'));

            /** @var \clients_history_actions $clientHistoryActions */
            $clientHistoryActions = $this->get('unilend.service.entity_manager')->getRepository('clients_history_actions');
            $clientHistoryActions->histo(7, 'mdp oublié', $client->id_client, serialize(['id_client' => $client->id_client, 'newmdp' => md5($post['client_new_password'])]));
        }

        return $this->redirectToRoute('define_new_password', ['clientHash' => $client->hash]);
    }

    private function sendPasswordModificationEmail(\clients $client)
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Facebook', 'type');
        $lien_fb = $settings->value;
        $settings->get('Twitter', 'type');
        $lien_tw = $settings->value;

        $varMail = [
            'surl'    => $this->get('assets.packages')->getUrl(''),
            'url'     => $this->get('assets.packages')->getUrl(''),
            'lien_fb' => $lien_fb,
            'lien_tw' => $lien_tw,
            'login'    => $client->email,
            'prenom_p' => $client->prenom
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('generation-mot-de-passe', $varMail);
        $message->setTo($client->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    /**
     * @Route("/security/ajax/password", name="security_ajax_password")
     * @Method("POST")
     */
    public function checkPassWordComplexityAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            /** @var TranslatorInterface $translator */
            $translator = $this->get('translator');

            if ($this->checkPasswordComplexity($request->request->get('client_password'))) {
                return new JsonResponse([
                    'status' => true
                ]);
            } else {
                return new JsonResponse([
                    'status' => false,
                    'error' => $translator->trans('common-validator_password-invalid')
                ]);
            }
        }

        return new Response('not an ajax request');
    }

    private function checkPasswordComplexity($password)
    {
        /** @var BCryptPasswordEncoder $securityPasswordEncoder */
        $securityPasswordEncoder = $this->get('unilend.frontbundle.security.password_encoder');

        try {
            $securityPasswordEncoder->encodePassword($password, '');
        } catch (BadCredentialsException $exception) {
            return false;
        }

        return true;
    }
}
