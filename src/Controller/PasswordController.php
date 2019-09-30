<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swift_Mailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\{Annotation\Route, Generator\UrlGeneratorInterface};
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\TemporaryToken;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\TemporaryLinksLogin;
use Unilend\Form\User\ResetPasswordType;
use Unilend\Repository\{ClientsRepository, TemporaryTokenRepository};
use Unilend\Service\GoogleRecaptchaManager;
use Unilend\SwiftMailer\TemplateMessageProvider;

class PasswordController extends AbstractController
{
    /**
     * In order not to disclose personal information (existence of account on the platform),
     * success message is always displayed, except for invalid email format or CSRF token.
     *
     * @Route("/mot-de-passe", name="password_reset_request", methods={"POST"}, condition="request.isXmlHttpRequest()")
     *
     * @param Request                  $request
     * @param ClientsRepository        $clientsRepository
     * @param TemporaryTokenRepository $temporaryTokenRepository
     * @param TemplateMessageProvider  $templateMessageProvider
     * @param GoogleRecaptchaManager   $googleRecaptchaManager
     * @param Swift_Mailer            $mailer
     * @param TranslatorInterface      $translator
     * @param LoggerInterface          $logger
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return Response
     */
    public function resetRequest(
        Request $request,
        ClientsRepository $clientsRepository,
        TemporaryTokenRepository $temporaryTokenRepository,
        TemplateMessageProvider $templateMessageProvider,
        GoogleRecaptchaManager $googleRecaptchaManager,
        Swift_Mailer $mailer,
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

        $token    = $temporaryTokenRepository->generateShortTemporaryToken($client);
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
     * @Route("/mot-de-passe/{securityToken}", name="password_reset", requirements={"securityToken": "[a-z0-9]{32}"}, methods={"GET", "POST"})
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     *
     * @param Request                      $request
     * @param TemporaryToken               $temporaryToken
     * @param TemporaryTokenRepository     $temporaryTokenRepository
     * @param TranslatorInterface          $translator
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     * @param ClientsRepository            $clientsRepository
     *
     * @throws ORMException
     * @throws Exception
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function reset(
        Request $request,
        TemporaryToken $temporaryToken,
        TemporaryTokenRepository $temporaryTokenRepository,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $userPasswordEncoder,
        ClientsRepository $clientsRepository
    ): Response {
        if ($this->get('session')->getFlashBag()->has('passwordSuccess')) {
            return $this->render('security/password_reset.html.twig', ['token' => $temporaryToken->getToken()]);
        }

        if (!$temporaryToken->isValid()) {
            $this->addFlash('tokenError', $translator->trans('reset-password.invalid-link-error-message'));

            return $this->render('security/password_reset.html.twig', ['token' => $temporaryToken->getToken()]);
        }

        $temporaryToken->access();

        $form = $this->createForm(ResetPasswordType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $client   = $temporaryToken->getClient();

            if (md5($formData['securityQuestion']['securityAnswer']) === $client->getSecurityAnswer()) {
                $encryptedPassword = $userPasswordEncoder->encodePassword($client, $formData['password']['plainPassword']);
                $client->setPassword($encryptedPassword);

                $clientsRepository->save($client);
                $temporaryTokenRepository->save($temporaryToken);

                $this->addFlash('passwordSuccess', $translator->trans('reset-password.reset-success-message'));
            } else {
                $this->addFlash('passwordErrors', $translator->trans('common-validator.secret-answer-invalid'));
            }
        }

        return $this->render('security/password_reset.html.twig', [
            'secretQuestion' => $temporaryToken->getClient()->getSecurityQuestion(),
            'form'           => $form->createView(),
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
}
