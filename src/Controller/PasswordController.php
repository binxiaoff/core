<?php

declare(strict_types=1);

namespace Unilend\Controller;

use DateTime;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swift_RfcComplianceException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{ClientsHistoryActions, TemporaryLinksLogin};
use Unilend\Repository\{ClientsRepository, TemporaryLinksLoginRepository};
use Unilend\Security\BCryptPasswordEncoder;
use Unilend\Service\Front\FormManager;
use Unilend\Service\GoogleRecaptchaManager;
use Unilend\SwiftMailer\{TemplateMessageProvider, UnilendMailer};

class PasswordController extends AbstractController
{
    /**
     * @Route("/mot-de-passe/initialisation/{securityToken}", name="password_init", requirements={"securityToken": "[0-9a-f]+"}, methods={"GET"})
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     *
     * @param TemporaryLinksLogin           $temporaryLink
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     *
     * @throws Exception
     *
     * @return Response
     */
    public function init(TemporaryLinksLogin $temporaryLink, TemporaryLinksLoginRepository $temporaryLinksLoginRepository): Response
    {
        $now         = new DateTime();
        $linkExpires = $temporaryLink->getExpires();
        $isValidLink = false;

        if ($linkExpires > $now) {
            $client = $temporaryLink->getIdClient();

            if (null === $client || false === $client->isValidated()) {
                return $this->redirectToRoute('home');
            }

            if (empty($client->getPassword())) {
                $isValidLink = true;

                $temporaryLink->setAccessed($now);
                $temporaryLinksLoginRepository->save($temporaryLink);
            }
        }

        return $this->render('security/password_init.html.twig', ['validLink' => $isValidLink, 'token' => $temporaryLink->getToken()]);
    }

    /**
     * @Route("/mot-de-passe/initialisation/{securityToken}", name="password_init_form", requirements={"securityToken": "[0-9a-f]+"}, methods={"POST"})
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     *
     * @param TemporaryLinksLogin           $temporaryLink
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param ClientsRepository             $clientsRepository
     * @param Request                       $request
     * @param TranslatorInterface           $translator
     * @param UserPasswordEncoderInterface  $userPasswordEncoder
     *
     * @throws Exception
     *
     * @return Response
     */
    public function initForm(
        TemporaryLinksLogin $temporaryLink,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        ClientsRepository $clientsRepository,
        Request $request,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $userPasswordEncoder
    ): Response {
        $now         = new DateTime();
        $linkExpires = $temporaryLink->getExpires();

        if ($linkExpires <= $now) {
            return $this->redirectToRoute('password_init', ['securityToken' => $temporaryLink->getToken()]);
        }

        $client = $temporaryLink->getIdClient();

        if (null === $client || false === $client->isValidated()) {
            return $this->redirectToRoute('home');
        }

        if (false === empty($client->getPassword())) {
            return $this->redirectToRoute('password_init', ['securityToken' => $temporaryLink->getToken()]);
        }

        $password     = $request->request->get('password');
        $confirmation = $request->request->get('confirmation');
        $question     = $request->request->get('question');
        $answer       = $request->request->get('answer');

        try {
            if (empty($password)) {
                throw new Exception('password empty');
            }
            $encryptedPassword = $userPasswordEncoder->encodePassword($client, $password);
        } catch (Exception $exception) {
            $this->addFlash('error', $translator->trans('common-validator.password-invalid'));
        }

        if ($password !== $confirmation) {
            $this->addFlash('error', $translator->trans('common-validator.password-not-equal'));
        }

        if (empty($question)) {
            $this->addFlash('error', $translator->trans('common-validator.secret-question-invalid'));
        }

        if (empty($answer)) {
            $this->addFlash('error', $translator->trans('common-validator.secret-answer-invalid'));
        }

        if (isset($encryptedPassword) && false === $this->get('session')->getFlashBag()->has('error')) {
            $client
                ->setPassword($encryptedPassword)
                ->setSecurityQuestion(filter_var($question, FILTER_SANITIZE_STRING))
                ->setSecurityAnswer($answer)
            ;
            $clientsRepository->save($client);

            $temporaryLink->setExpires($now);
            $temporaryLinksLoginRepository->save($temporaryLink);

            return $this->redirectToRoute('login');
        }

        return $this->redirectToRoute('password_init', ['securityToken' => $temporaryLink->getToken()]);
    }

    /**
     * In order not to disclose personal information (existence of account on the platform),
     * success message is always displayed, except for invalid email format or CSRF token.
     *
     * @Route("/mot-de-passe", name="password_reset_request", methods={"POST"}, condition="request.isXmlHttpRequest()")
     *
     * @param Request                       $request
     * @param ClientsRepository             $clientsRepository
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param TemplateMessageProvider       $templateMessageProvider
     * @param GoogleRecaptchaManager        $googleRecaptchaManager
     * @param UnilendMailer                 $mailer
     * @param TranslatorInterface           $translator
     * @param LoggerInterface               $logger
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     *
     * @return Response
     */
    public function resetRequest(
        Request $request,
        ClientsRepository $clientsRepository,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        TemplateMessageProvider $templateMessageProvider,
        GoogleRecaptchaManager $googleRecaptchaManager,
        UnilendMailer $mailer,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ): Response {
        $email = $request->request->get('client_email');

        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => false,
                'error'   => $translator->trans('password-forgotten.invalid-email-format-error-message'),
            ]);
        }

        if (false === $this->isPasswordCSRFTokenValid($request)) {
            return new JsonResponse([
                'success' => false,
                'error'   => $translator->trans('password-forgotten.invalid-security-token-error-message'),
            ]);
        }

        if (false === $this->isPasswordCaptchaValid($request, $googleRecaptchaManager)) {
            return new JsonResponse([
                'success' => true,
            ]);
        }

        $client = $clientsRepository->findGrantedLoginAccountByEmail($email);

        if (null === $client) {
            return new JsonResponse([
                'success' => true,
            ]);
        }

        $token    = $temporaryLinksLoginRepository->generateTemporaryLink($client, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_SHORT);
        $keywords = [
            'firstName'    => $client->getFirstName(),
            'email'        => $client->getEmail(),
            'passwordLink' => $this->generateUrl('password_reset', ['securityToken' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $message = $templateMessageProvider->newMessage('forgotten-password', $keywords);

        try {
            $message->setTo($client->getEmail());
            $mailer->send($message);
        } catch (Exception $exception) {
            $logger->warning(sprintf('Could not send email "forgotten-password" - Exception: %s', $exception->getMessage()), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $client->getIdClient(),
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine(),
            ]);
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/mot-de-passe/{securityToken}", name="password_reset", requirements={"securityToken": "[a-z0-9]{32}"}, methods={"GET"})
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     *
     * @param TemporaryLinksLogin           $temporaryLink
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param TranslatorInterface           $translator
     *
     * @throws Exception
     *
     * @return Response
     */
    public function reset(TemporaryLinksLogin $temporaryLink, TemporaryLinksLoginRepository $temporaryLinksLoginRepository, TranslatorInterface $translator): Response
    {
        if ($this->get('session')->getFlashBag()->has('passwordSuccess')) {
            return $this->render('security/password_reset.html.twig', ['token' => $temporaryLink->getToken()]);
        }

        if ($temporaryLink->getExpires() < new DateTime()) {
            $this->addFlash('tokenError', $translator->trans('reset-password.invalid-link-error-message'));

            return $this->render('security/password_reset.html.twig', ['token' => $temporaryLink->getToken()]);
        }

        $temporaryLink->setAccessed(new DateTime());
        $temporaryLinksLoginRepository->save($temporaryLink);

        return $this->render('security/password_reset.html.twig', [
            'token'          => $temporaryLink->getToken(),
            'secretQuestion' => $temporaryLink->getIdClient()->getSecurityQuestion(),
        ]);
    }

    /**
     * @Route("/mot-de-passe/{securityToken}", name="password_reset_form", requirements={"securityToken": "[a-z0-9]{32}"}, methods={"POST"})
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     *
     * @param TemporaryLinksLogin           $temporaryLink
     * @param Request                       $request
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param ClientsRepository             $clientsRepository
     * @param FormManager                   $formManager
     * @param UserPasswordEncoderInterface  $userPasswordEncoder
     * @param TranslatorInterface           $translator
     * @param LoggerInterface               $logger
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return Response
     */
    public function resetForm(
        TemporaryLinksLogin $temporaryLink,
        Request $request,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        ClientsRepository $clientsRepository,
        FormManager $formManager,
        UserPasswordEncoderInterface $userPasswordEncoder,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ): Response {
        if ($temporaryLink->getExpires() < new DateTime()) {
            $this->addFlash('tokenError', $translator->trans('reset-password.invalid-link-error-message'));

            return $this->redirectToRoute('password_reset', ['securityToken' => $temporaryLink->getToken()]);
        }

        $temporaryLink->setAccessed(new DateTime());
        $temporaryLinksLoginRepository->save($temporaryLink);

        $client       = $temporaryLink->getIdClient();
        $password     = $request->request->get('password');
        $confirmation = $request->request->get('confirmation');
        $answer       = $request->request->get('answer');

        if (empty($password)) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator.missing-new-password'));
        }

        if (empty($confirmation)) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator.missing-new-password-confirmation'));
        }

        if ($password !== $confirmation) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator.password-not-equal'));
        }

        if (empty($answer) || md5($answer) !== $client->getSecurityAnswer()) {
            $this->addFlash('passwordErrors', $translator->trans('common-validator.secret-answer-invalid'));
        }

        if (false === $this->get('session')->getFlashBag()->has('passwordErrors')) {
            try {
                $encryptedPassword = $userPasswordEncoder->encodePassword($client, $password);
                $temporaryLinksLoginRepository->revokeTemporaryLinks($client);
                $client->setPassword($encryptedPassword);
                $clientsRepository->save($client);

                $this->addFlash('passwordSuccess', $translator->trans('reset-password.reset-success-message'));

                try {
                    $formManager->saveFormSubmission(
                        $client,
                        ClientsHistoryActions::CHANGE_PASSWORD,
                        serialize(['id_client' => $client->getIdClient(), 'new' => md5($password)]),
                        $request->getClientIp()
                    );
                } catch (OptimisticLockException $exception) {
                    $logger->error(sprintf('Could not save the password modification form submission log - Exception: %s', $exception->getMessage()), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine(),
                    ]);
                }
            } catch (BadCredentialsException $exception) {
                $this->addFlash('passwordErrors', $translator->trans('common-validator.password-invalid'));
            } catch (OptimisticLockException $exception) {
                $logger->critical(sprintf('Could not save the password modification to the database - Exception: %s', $exception->getMessage()), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                ]);
            }
        }

        return $this->redirectToRoute('password_reset', ['securityToken' => $temporaryLink->getToken()]);
    }

    /**
     * @Route("/mot-de-passe/verification", name="password_check", condition="request.isXmlHttpRequest()", methods={"POST"})
     *
     * @param Request               $request
     * @param TranslatorInterface   $translator
     * @param BCryptPasswordEncoder $passwordEncoder
     *
     * @return JsonResponse|Response
     */
    public function checkPassword(Request $request, TranslatorInterface $translator, BCryptPasswordEncoder $passwordEncoder): Response
    {
        if ($this->checkPasswordComplexity($request->request->get('client_password'), $passwordEncoder)) {
            return new JsonResponse([
                'status' => true,
            ]);
        }

        return new JsonResponse([
            'status' => false,
            'error'  => $translator->trans('common-validator.password-invalid'),
        ]);
    }

    /**
     * @Route("/mot-de-passe/csrf-token/{tokenId}", name="password_csrf_token", condition="request.isXmlHttpRequest()")
     *
     * @param string $tokenId
     *
     * @return string
     */
    public function csrfToken($tokenId)
    {
        $tokenId      = filter_var($tokenId, FILTER_SANITIZE_STRING);
        $tokenManager = $this->get('security.csrf.token_manager');
        $token        = $tokenManager->getToken($tokenId);

        return $this->json($token->getValue());
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
