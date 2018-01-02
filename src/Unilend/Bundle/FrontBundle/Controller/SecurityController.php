<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsHistoryActions;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\FrontBundle\Security\BCryptPasswordEncoder;


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
        $pageData            = [
            'last_username'      => $lastUsername,
            'error'              => $error,
            'captchaInformation' => $request->getSession()->get('captchaInformation', [])
        ];

        $request->getSession()->remove('captchaInformation');

        return $this->render('security/login.html.twig', $pageData);
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
    public function handlePasswordForgottenAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $entityManagerSimulator = $this->get('unilend.service.entity_manager');
            /** @var \clients $client */
            $client = $entityManagerSimulator->getRepository('clients');
            $email   = $request->request->get('client_email');

            if (filter_var($email, FILTER_VALIDATE_EMAIL) && $client->get($email, 'status = ' . Clients::STATUS_ONLINE . ' AND email')) {
                /** @var \temporary_links_login $temporaryLink */
                $temporaryLink = $entityManagerSimulator->getRepository('temporary_links_login');
                $token         = $temporaryLink->generateTemporaryLink($client->id_client, \temporary_links_login::PASSWORD_TOKEN_LIFETIME_SHORT);
                $wallet        = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);

                $keywords = [
                    'firstName'     => $client->prenom,
                    'login'         => $client->email,
                    'passwordLink'  => $this->generateUrl('define_new_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                    'lenderPattern' => $wallet->getWireTransferPattern()
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('mot-de-passe-oublie', $keywords);

                try {
                    $message->setTo($client->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $this->get('logger')->warning(
                        'Could not send email : mot-de-passe-oublie - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );

                    return new JsonResponse('nok');
                }

                return new JsonResponse('ok');
            } else {
                return new JsonResponse('nok');
            }
        } else {
            return new Response('not an ajax request');
        }
    }

    /**
     * @Route("/nouveau-mot-de-passe/{token}", name="define_new_password", requirements={"token": "[a-z0-9]{32}"})
     *
     * @param string $token
     * @return Response
     */
    public function passwordForgottenAction($token)
    {
        $entityManager = $this->get('unilend.service.entity_manager');

        if ($this->get('session')->getFlashBag()->has('passwordSuccess')) {
            return $this->render('security/password_forgotten.html.twig', ['token' => $token]);
        }

        /** @var \temporary_links_login $temporaryLink */
        $temporaryLink = $entityManager->getRepository('temporary_links_login');

        if (false === $temporaryLink->get($token, 'expires > NOW() AND token')) {
            $this->addFlash('tokenError', $this->get('translator')->trans('password-forgotten_invalid-token'));
            return $this->render('security/password_forgotten.html.twig', ['token' => $token]);
        }

        $temporaryLink->accessed = (new \DateTime('NOW'))->format('Y-m-d H:i:s');
        $temporaryLink->update();

        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        $client->get($temporaryLink->id_client, 'id_client');

        return $this->render('security/password_forgotten.html.twig', ['token' => $token, 'secretQuestion' => $client->secrete_question]);
    }

    /**
     * @Route("/nouveau-mot-de-passe/submit/{token}", name="save_new_password", requirements={"token": "[a-z0-9]{32}"})
     * @Method("POST")
     *
     * @param string  $token
     * @param Request $request
     * @return Response
     */
    public function changePasswordFormAction($token, Request $request)
    {
        $entityManager = $this->get('unilend.service.entity_manager');

        /** @var \temporary_links_login $temporaryLink */
        $temporaryLink = $entityManager->getRepository('temporary_links_login');

        if (false === $temporaryLink->get($token, 'expires > NOW() AND token')) {
            $this->addFlash('tokenError', $this->get('translator')->trans('password-forgotten_invalid-token'));
            return $this->redirectToRoute('define_new_password', ['token' => $token]);
        }

        $temporaryLink->accessed = (new \DateTime('NOW'))->format('Y-m-d H:i:s');
        $temporaryLink->update();

        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        $client->get($temporaryLink->id_client, 'id_client');

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        if (empty($request->request->get('client_new_password'))) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_missing-new-password'));
        }
        if (empty($request->request->get('client_new_password_confirmation'))) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_missing-new-password-confirmation'));
        }
        if (empty($request->request->get('client_secret_answer')) || md5($request->request->get('client_secret_answer')) !== $client->secrete_reponse) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_secret-answer-invalid'));
        }
        $password = '';
        if (false === empty($request->request->get('client_new_password')) && false === empty($request->request->get('client_new_password_confirmation')) && false === empty($request->request->get('client_secret_answer'))) {
            if ($request->request->get('client_new_password') !== $request->request->get('client_new_password_confirmation')) {
                $this->addFlash('passwordErrors', $translator->trans('common-validator_password-not-equal'));
            }

            $user = $this->get('unilend.frontbundle.security.user_provider')->loadUserByUsername($client->email);
            try {
                $password = $this->get('security.password_encoder')->encodePassword($user, $request->request->get('client_new_password'));
            } catch (\Exception $exception) {
                $this->addFlash('passwordErrors', $translator->trans('common-validator_password-invalid'));
            }
        }

        if (false === $this->get('session')->getFlashBag()->has('passwordErrors')) {
            $temporaryLink->revokeTemporaryLinks($temporaryLink->id_client);
            $client->password = $password;
            $client->update();

            try {
                $this->sendPasswordModificationEmail($client);
            } catch (\Exception $exception) {
                $this->get('logger')->error(
                    'Could not send password modification email - Exception: ' . $exception->getMessage(),
                    ['id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
            $this->addFlash('passwordSuccess', $translator->trans('password-forgotten_new-password-form-success-message'));

            $formManager = $this->get('unilend.frontbundle.service.form_manager');
            $formManager->saveFormSubmission($client, ClientsHistoryActions::CHANGE_PASSWORD, serialize(['id_client' => $client->id_client, 'newmdp' => md5($request->request->get('client_new_password'))]), $request->getClientIp());
        }

        return $this->redirectToRoute('define_new_password', ['token' => $token]);
    }

    /**
     * @param \clients $client
     *
     * @throws \Swift_RfcComplianceException
     */
    private function sendPasswordModificationEmail(\clients $client)
    {
        $keywords = [
            'firstName'     => $client->prenom,
            'password'      => '',
            'lenderPattern' => $client->getLenderPattern($client->id_client)
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('generation-mot-de-passe', $keywords);
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
        if ($request->isXmlHttpRequest()) {
            /** @var TranslatorInterface $translator */
            $translator = $this->get('translator');

            if ($this->checkPasswordComplexity($request->request->get('client_password'))) {
                return new JsonResponse([
                    'status' => true
                ]);
            } else {
                return new JsonResponse([
                    'status' => false,
                    'error'  => $translator->trans('common-validator_password-invalid')
                ]);
            }
        }

        return new Response('not an ajax request');
    }

    /**
     * @param string $password
     *
     * @return bool
     */
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

    /**
     * @Route("/security/csrf-token/{tokenId}", name="security_csrf_token", condition="request.isXmlHttpRequest()")
     *
     * @param string $tokenId
     *
     * @return string
     */
    public function csrfTokenAction($tokenId)
    {
        $tokenId          = filter_var($tokenId, FILTER_SANITIZE_STRING);
        $csrfTokenManager = $this->get('security.csrf.token_manager');
        $token            = $csrfTokenManager->getToken($tokenId);

        return $this->json($token->getValue());
    }
}
