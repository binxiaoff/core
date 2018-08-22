<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ClientsHistoryActions, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\GoogleRecaptchaManager;
use Unilend\Bundle\FrontBundle\Security\{BCryptPasswordEncoder, LoginAuthenticator};

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function loginAction(Request $request): Response
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('home');
        }

        /** @var AuthenticationUtils $authenticationUtils */
        $authenticationUtils = $this->get('security.authentication_utils');
        $error               = $authenticationUtils->getLastAuthenticationError();
        $lastUsername        = $authenticationUtils->getLastUsername();
        $pageData            = [
            'last_username'       => $lastUsername,
            'error'               => $error,
            'recaptchaKey'        => $this->getParameter('google.recaptcha_key'),
            'displayLoginCaptcha' => $request->getSession()->get(LoginAuthenticator::SESSION_NAME_LOGIN_CAPTCHA, false)
        ];

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
     * In order not to disclose personal information (existence of account on the platform),
     * success message is always displayed, except for invalid email format or CSRF token
     *
     * @Route("/pwd", name="pwd_forgotten", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handlePasswordForgottenAction(Request $request): Response
    {
        if (false === $request->isXmlHttpRequest()) {
            return new Response('not an ajax request');
        }

        $email = $request->request->get('client_email');

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => false,
                'error'   => $this->get('translator')->trans('password-forgotten_pop-up-error-invalid-email-format')
            ]);
        }

        if (false === $this->isPasswordCSRFTokenValid($request)) {
            return new JsonResponse([
                'success' => false,
                'error'   => $this->get('translator')->trans('password-forgotten_pop-up-error-invalid-security-token')
            ]);
        }

        if (false === $this->isPasswordCaptchaValid($request)) {
            return new JsonResponse([
                'success' => true
            ]);
        }

        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $clientsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $clients           = $clientsRepository->findGrantedLoginAccountByEmail($email);
        $clientsCount      = count($clients);

        if (0 === $clientsCount) {
            return new JsonResponse([
                'success' => true
            ]);
        }

        if ($clientsCount > 1) {
            $ids = [];
            foreach ($clients as $client) {
                $ids[] = $client->getIdClient();
            }

            $slackManager = $this->get('unilend.service.slack_manager');
            $slackManager->sendMessage('[Mot de passe oublié] L’adresse email ' . $email . ' est en doublon (' . $clientsCount . ' occurrences : ' . implode(', ', $ids) . ')', '#doublons-email');

            return new JsonResponse([
                'success' => true
            ]);
        }

        /** @var \temporary_links_login $temporaryLink */
        $temporaryLink = $this->get('unilend.service.entity_manager')->getRepository('temporary_links_login');
        $client        = current($clients);
        $token         = $temporaryLink->generateTemporaryLink($client->getIdClient(), \temporary_links_login::PASSWORD_TOKEN_LIFETIME_SHORT);
        $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idClient' => $client]);

        if (null === $wallet) {
            $this->get('logger')->error(
                'Client ' . $client->getIdClient() . ' has no wallet',
                ['id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );

            return new JsonResponse([
                'success' => true
            ]);
        }

        switch ($wallet->getIdType()->getLabel()) {
            case WalletType::LENDER:
                $mailType = 'mot-de-passe-oublie';
                $keywords = [
                    'firstName'     => $client->getPrenom(),
                    'login'         => $client->getEmail(),
                    'passwordLink'  => $this->generateUrl('define_new_password', ['securityToken' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                    'lenderPattern' => $wallet->getWireTransferPattern()
                ];
                break;
            case WalletType::BORROWER:
                $mailType = 'mot-de-passe-oublie-emprunteur';
                $keywords = [
                    'firstName'            => $client->getPrenom(),
                    'temporaryToken'       => $token,
                    'borrowerServiceEmail' => $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse emprunteur'])->getValue()
                ];
                break;
            case WalletType::PARTNER:
                $mailType = 'mot-de-passe-oublie-partenaire';
                $keywords = [
                    'firstName'     => $client->getPrenom(),
                    'login'         => $client->getEmail(),
                    'passwordLink'  => $this->generateUrl('define_new_password', ['securityToken' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                    'lenderPattern' => $wallet->getWireTransferPattern()
                ];
                break;
            default:
                $this->get('logger')->warn(
                    'Forgotten password functionality is not available for wallets of type "' . $wallet->getIdType()->getLabel() . '"',
                    ['id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );

                return new JsonResponse([
                    'success' => true
                ]);
        }

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage($mailType, $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->get('logger')->warning(
                'Could not send email "' . $mailType . '" - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        return new JsonResponse([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function isPasswordCSRFTokenValid(Request $request): bool
    {
        $token = $request->request->get('_csrf_token');

        if (empty($token)) {
            return false;
        }

        $csrfTokenManager = $this->get('security.csrf.token_manager');
        return $csrfTokenManager->isTokenValid(new CsrfToken('password', $token));
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function isPasswordCaptchaValid(Request $request): bool
    {
        $token = $request->request->get(GoogleRecaptchaManager::FORM_FIELD_NAME);

        if (empty($token)) {
            return false;
        }

        $googleRecaptchaManager = $this->get('unilend.service.google_recaptcha_manager');
        return $googleRecaptchaManager->isValid($token);
    }

    /**
     * @Route("/nouveau-mot-de-passe/{securityToken}", name="define_new_password", requirements={"securityToken": "[a-z0-9]{32}"})
     *
     * @param string $securityToken
     *
     * @return Response
     */
    public function passwordForgottenAction(string $securityToken): Response
    {
        $entityManager = $this->get('unilend.service.entity_manager');

        if ($this->get('session')->getFlashBag()->has('passwordSuccess')) {
            return $this->render('security/password_forgotten.html.twig', ['token' => $securityToken]);
        }

        /** @var \temporary_links_login $temporaryLink */
        $temporaryLink = $entityManager->getRepository('temporary_links_login');

        if (false === $temporaryLink->get($securityToken, 'expires > NOW() AND token')) {
            $this->addFlash('tokenError', $this->get('translator')->trans('password-forgotten_invalid-token'));
            return $this->render('security/password_forgotten.html.twig', ['token' => $securityToken]);
        }

        $temporaryLink->accessed = (new \DateTime('NOW'))->format('Y-m-d H:i:s');
        $temporaryLink->update();

        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        $client->get($temporaryLink->id_client, 'id_client');

        return $this->render('security/password_forgotten.html.twig', ['token' => $securityToken, 'secretQuestion' => $client->secrete_question]);
    }

    /**
     * @Route("/nouveau-mot-de-passe/submit/{securityToken}", name="save_new_password",
     *     requirements={"securityToken": "[a-z0-9]{32}"}, methods={"POST"})
     *
     * @param string  $securityToken
     * @param Request $request
     *
     * @return Response
     */
    public function changePasswordFormAction(string $securityToken, Request $request): Response
    {
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        /** @var \temporary_links_login $temporaryLink */
        $temporaryLink = $entityManagerSimulator->getRepository('temporary_links_login');

        if (false === $temporaryLink->get($securityToken, 'expires > NOW() AND token')) {
            $this->addFlash('tokenError', $this->get('translator')->trans('password-forgotten_invalid-token'));
            return $this->redirectToRoute('define_new_password', ['securityToken' => $securityToken]);
        }

        $temporaryLink->accessed = (new \DateTime('NOW'))->format('Y-m-d H:i:s');
        $temporaryLink->update();

        $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($temporaryLink->id_client);

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        if (empty($request->request->get('client_new_password'))) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_missing-new-password'));
        }
        if (empty($request->request->get('client_new_password_confirmation'))) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_missing-new-password-confirmation'));
        }
        if (empty($request->request->get('client_secret_answer')) || md5($request->request->get('client_secret_answer')) !== $client->getSecreteReponse()) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_secret-answer-invalid'));
        }

        if (false === empty($request->request->get('client_new_password')) && false === empty($request->request->get('client_new_password_confirmation')) && false === empty($request->request->get('client_secret_answer'))) {
            if ($request->request->get('client_new_password') !== $request->request->get('client_new_password_confirmation')) {
                $this->addFlash('passwordErrors', $translator->trans('common-validator_password-not-equal'));
            }

            try {
                $password = $this->get('security.password_encoder')->encodePassword($client, $request->request->get('client_new_password'));
                $temporaryLink->revokeTemporaryLinks($temporaryLink->id_client);
                $client->setPassword($password);
                $entityManager->flush($client);

                try {
                    $this->sendPasswordModificationEmail($client);
                } catch (\Exception $exception) {
                    $this->get('logger')->error(
                        'Could not send password modification email - Exception: ' . $exception->getMessage(), [
                            'id_client' => $client->getIdClient(),
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'file'      => $exception->getFile(),
                            'line'      => $exception->getLine(),
                        ]
                    );
                }
                $this->addFlash('passwordSuccess', $translator->trans('password-forgotten_new-password-form-success-message'));

                $formManager = $this->get('unilend.frontbundle.service.form_manager');

                try {
                    $formManager->saveFormSubmission($client, ClientsHistoryActions::CHANGE_PASSWORD,
                        serialize(['id_client' => $client->getIdClient(), 'newmdp' => md5($request->request->get('client_new_password'))]), $request->getClientIp());
                } catch (OptimisticLockException $exception) {
                    $this->get('logger')->error(
                        'Could not save the password modification form submission log to the database - Exception: ' . $exception->getMessage(), [
                            'id_client' => $client->getIdClient(),
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'file'      => $exception->getFile(),
                            'line'      => $exception->getLine(),
                        ]
                    );
                }
            } catch (BadCredentialsException $exception) {
                $this->addFlash('passwordErrors', $translator->trans('common-validator_password-invalid'));
            } catch (OptimisticLockException $exception) {
                $this->get('logger')->critical(
                    'Could not save the password modification to the database - Exception: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine(),
                    ]
                );
            }
        }

        return $this->redirectToRoute('define_new_password', ['securityToken' => $securityToken]);
    }

    /**
     * @param Clients $client
     *
     * @throws \Swift_RfcComplianceException
     * @throws \Exception
     */
    private function sendPasswordModificationEmail(Clients $client)
    {
        $keywords = [
            'firstName'     => $client->getPrenom(),
            'password'      => '',
            'lenderPattern' => $this->get('unilend.service.lender_manager')->getLenderPattern($client)
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('generation-mot-de-passe', $keywords);
        $message->setTo($client->getEmail());
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    /**
     * @Route("/security/ajax/password", name="security_ajax_password", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function checkPassWordComplexityAction(Request $request): Response
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
