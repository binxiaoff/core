<?php

declare(strict_types=1);

namespace Unilend\Controller;

use DateTime;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Swift_RfcComplianceException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{Clients, ClientsHistoryActions, TemporaryLinksLogin, WalletType};
use Unilend\Repository\{ClientsRepository, SettingsRepository, TemporaryLinksLoginRepository, WalletRepository};
use Unilend\Security\{BCryptPasswordEncoder, LoginAuthenticator};
use Unilend\Service\{Front\FormManager, GoogleRecaptchaManager, LenderManager, SlackManager};
use Unilend\SwiftMailer\{TemplateMessage, TemplateMessageProvider, UnilendMailer};

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
     * success message is always displayed, except for invalid email format or CSRF token.
     *
     * @Route("/pwd", name="pwd_forgotten", methods={"POST"})
     *
     * @param Request                       $request
     * @param ClientsRepository             $clientsRepository
     * @param WalletRepository              $walletRepository
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param SettingsRepository            $settingsRepository
     * @param SlackManager                  $slackManager
     * @param TemplateMessageProvider       $templateMessageProvider
     * @param LoggerInterface               $logger
     * @param UnilendMailer                 $mailer
     *
     * @throws Swift_RfcComplianceException
     *
     * @return Response
     */
    public function handlePasswordForgottenAction(
        Request $request,
        ClientsRepository $clientsRepository,
        WalletRepository $walletRepository,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        SettingsRepository $settingsRepository,
        SlackManager $slackManager,
        TemplateMessageProvider $templateMessageProvider,
        LoggerInterface $logger,
        UnilendMailer $mailer
    ): Response {
        if (false === $request->isXmlHttpRequest()) {
            return new Response('not an ajax request');
        }

        $email = $request->request->get('client_email');

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => false,
                'error'   => $this->get('translator')->trans('password-forgotten_pop-up-error-invalid-email-format'),
            ]);
        }

        if (false === $this->isPasswordCSRFTokenValid($request)) {
            return new JsonResponse([
                'success' => false,
                'error'   => $this->get('translator')->trans('password-forgotten_pop-up-error-invalid-security-token'),
            ]);
        }

        if (false === $this->isPasswordCaptchaValid($request)) {
            return new JsonResponse([
                'success' => true,
            ]);
        }

        $clients      = $clientsRepository->findGrantedLoginAccountsByEmail($email);
        $clientsCount = count($clients);

        if (0 === $clientsCount) {
            return new JsonResponse([
                'success' => true,
            ]);
        }

        if ($clientsCount > 1) {
            $ids = [];
            foreach ($clients as $client) {
                $ids[] = $client->getIdClient();
            }

            $slackManager->sendMessage(
                sprintf('[Mot de passe oublié] L’adresse email %s est en doublon (%s occurrences : %s)', $email, $clientsCount, implode(', ', $ids)),
                '#doublons-email'
            );

            return new JsonResponse([
                'success' => true,
            ]);
        }

        $client = current($clients);
        $wallet = $walletRepository->findOneBy(['idClient' => $client]);
        $token  = $temporaryLinksLoginRepository->generateTemporaryLink($client, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_SHORT);

        if (null === $wallet) {
            $logger->error('Client ' . $client->getIdClient() . ' has no wallet', [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
            ]);

            return new JsonResponse([
                'success' => true,
            ]);
        }

        switch ($wallet->getIdType()->getLabel()) {
            case WalletType::LENDER:
                $mailType = 'mot-de-passe-oublie';
                $keywords = [
                    'firstName'     => $client->getFirstName(),
                    'login'         => $client->getEmail(),
                    'passwordLink'  => $this->generateUrl('define_new_password', ['securityToken' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                    'lenderPattern' => $wallet->getWireTransferPattern(),
                ];

                break;
            case WalletType::BORROWER:
                $mailType = 'mot-de-passe-oublie-emprunteur';
                $keywords = [
                    'firstName'            => $client->getFirstName(),
                    'temporaryToken'       => $token,
                    'borrowerServiceEmail' => $settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue(),
                ];

                break;
            case WalletType::PARTNER:
                $mailType = 'mot-de-passe-oublie-partenaire';
                $keywords = [
                    'firstName'     => $client->getFirstName(),
                    'login'         => $client->getEmail(),
                    'passwordLink'  => $this->generateUrl('define_new_password', ['securityToken' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                    'lenderPattern' => $wallet->getWireTransferPattern(),
                ];

                break;
            default:
                $logger->warning(
                    'Forgotten password functionality is not available for wallets of type "' . $wallet->getIdType()->getLabel() . '"',
                    ['id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );

                return new JsonResponse([
                    'success' => true,
                ]);
        }

        $message = $templateMessageProvider->newMessage($mailType, $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer->send($message);
        } catch (Exception $exception) {
            $logger->warning(
                sprintf('Could not send email %s - Exception: %s', $mailType, $exception->getMessage()),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/nouveau-mot-de-passe/{securityToken}", name="define_new_password", requirements={"securityToken": "[a-z0-9]{32}"})
     *
     * @param string                        $securityToken
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     *
     * @throws Exception
     *
     * @return Response
     */
    public function passwordForgottenAction(string $securityToken, TemporaryLinksLoginRepository $temporaryLinksLoginRepository): Response
    {
        if ($this->get('session')->getFlashBag()->has('passwordSuccess')) {
            return $this->render('security/password_forgotten.html.twig', ['token' => $securityToken]);
        }

        $temporaryLink = $temporaryLinksLoginRepository->findOneBy(['token' => $securityToken]);

        /** @var TemporaryLinksLogin $temporaryLink */
        if (null === $temporaryLink || $temporaryLink->getExpires() < new DateTime()) {
            $this->addFlash('tokenError', $this->get('translator')->trans('password-forgotten_invalid-token'));

            return $this->render('security/password_forgotten.html.twig', ['token' => $securityToken]);
        }

        $temporaryLink->setAccessed(new DateTime());
        $temporaryLinksLoginRepository->save($temporaryLink);

        return $this->render('security/password_forgotten.html.twig', [
            'token'          => $securityToken,
            'secretQuestion' => $temporaryLink->getIdClient()->getSecurityQuestion(),
        ]);
    }

    /**
     * @Route("/nouveau-mot-de-passe/submit/{securityToken}", name="save_new_password",
     * requirements={"securityToken": "[a-z0-9]{32}"}, methods={"POST"})
     *
     * @param string                        $securityToken
     * @param Request                       $request
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param ClientsRepository             $clientsRepository
     * @param FormManager                   $formManager
     * @param LenderManager                 $lenderManager
     * @param TemplateMessageProvider       $templateMessageProvider
     * @param UnilendMailer                 $mailer
     * @param TranslatorInterface           $translator
     * @param LoggerInterface               $logger
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return Response
     */
    public function changePasswordFormAction(
        string $securityToken,
        Request $request,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        ClientsRepository $clientsRepository,
        FormManager $formManager,
        LenderManager $lenderManager,
        TemplateMessageProvider $templateMessageProvider,
        UnilendMailer $mailer,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ): Response {
        $temporaryLink = $temporaryLinksLoginRepository->findOneBy(['token' => $securityToken]);

        /** @var TemporaryLinksLogin $temporaryLink */
        if (null === $temporaryLink || $temporaryLink->getExpires() < new DateTime()) {
            $this->addFlash('tokenError', $this->get('translator')->trans('password-forgotten_invalid-token'));

            return $this->redirectToRoute('define_new_password', ['securityToken' => $securityToken]);
        }

        $temporaryLink->setAccessed(new DateTime());
        $temporaryLinksLoginRepository->save($temporaryLink);

        $client = $temporaryLink->getIdClient();

        if (empty($request->request->get('client_new_password'))) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_missing-new-password'));
        }
        if (empty($request->request->get('client_new_password_confirmation'))) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_missing-new-password-confirmation'));
        }
        if (empty($request->request->get('client_secret_answer')) || md5($request->request->get('client_secret_answer')) !== $client->getSecurityAnswer()) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator_secret-answer-invalid'));
        }

        if (false === empty($request->request->get('client_new_password'))
            && false === empty($request->request->get('client_new_password_confirmation'))
            && false === empty($request->request->get('client_secret_answer'))) {
            if ($request->request->get('client_new_password') !== $request->request->get('client_new_password_confirmation')) {
                $this->addFlash('passwordErrors', $translator->trans('common-validator_password-not-equal'));
            }

            try {
                $password = $this->get('security.password_encoder')->encodePassword($client, $request->request->get('client_new_password'));
                $temporaryLinksLoginRepository->revokeTemporaryLinks($client);
                $client->setPassword($password);
                $clientsRepository->save($client);

                try {
                    $this->sendPasswordModificationEmail($client, $lenderManager, $templateMessageProvider, $mailer);
                } catch (Exception $exception) {
                    $logger->error(
                        'Could not send password modification email - Exception: ' . $exception->getMessage(),
                        [
                            'id_client' => $client->getIdClient(),
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'file'      => $exception->getFile(),
                            'line'      => $exception->getLine(),
                        ]
                    );
                }
                $this->addFlash('passwordSuccess', $translator->trans('password-forgotten_new-password-form-success-message'));

                try {
                    $formManager->saveFormSubmission(
                        $client,
                        ClientsHistoryActions::CHANGE_PASSWORD,
                        serialize(['id_client' => $client->getIdClient(), 'newmdp' => md5($request->request->get('client_new_password'))]),
                        $request->getClientIp()
                    );
                } catch (OptimisticLockException $exception) {
                    $logger->error(
                        'Could not save the password modification form submission log to the database - Exception: ' . $exception->getMessage(),
                        [
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
                $logger->critical(
                    sprintf('Could not save the password modification to the database - Exception: %s', $exception->getMessage()),
                    [
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
     * @Route("/security/ajax/password", name="security_ajax_password", condition="request.isXmlHttpRequest()", methods={"POST"})
     *
     * @param Request               $request
     * @param TranslatorInterface   $translator
     * @param BCryptPasswordEncoder $passwordEncoder
     *
     * @return JsonResponse|Response
     */
    public function checkPassWordComplexityAction(Request $request, TranslatorInterface $translator, BCryptPasswordEncoder $passwordEncoder): Response
    {
        if ($this->checkPasswordComplexity($request->request->get('client_password'), $passwordEncoder)) {
            return new JsonResponse([
                'status' => true,
            ]);
        }

        return new JsonResponse([
            'status' => false,
            'error'  => $translator->trans('common-validator_password-invalid'),
        ]);
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
     * @param Request                $request
     * @param GoogleRecaptchaManager $googleRecaptchaManager
     *
     * @return bool
     */
    private function isPasswordCaptchaValid(Request $request, GoogleRecaptchaManager $googleRecaptchaManager): bool
    {
        $token = $request->request->get(GoogleRecaptchaManager::FORM_FIELD_NAME);

        if (empty($token)) {
            return false;
        }

        return $googleRecaptchaManager->isValid($token);
    }

    /**
     * @param Clients                 $client
     * @param LenderManager           $lenderManager
     * @param TemplateMessageProvider $templateMessageProvider
     * @param UnilendMailer           $mailer
     *
     * @throws Swift_RfcComplianceException
     * @throws Exception
     */
    private function sendPasswordModificationEmail(Clients $client, LenderManager $lenderManager, TemplateMessageProvider $templateMessageProvider, UnilendMailer $mailer)
    {
        $keywords = [
            'firstName'     => $client->getFirstName(),
            'password'      => '',
            'lenderPattern' => $lenderManager->getLenderPattern($client),
        ];

        /** @var TemplateMessage $message */
        $message = $templateMessageProvider->newMessage('generation-mot-de-passe', $keywords);
        $message->setTo($client->getEmail());
        $mailer->send($message);
    }

    /**
     * @param string                $password
     * @param BCryptPasswordEncoder $passwordEncoder
     *
     * @return bool
     */
    private function checkPasswordComplexity($password, BCryptPasswordEncoder $passwordEncoder)
    {
        try {
            $passwordEncoder->encodePassword($password, '');
        } catch (BadCredentialsException $exception) {
            return false;
        }

        return true;
    }
}
