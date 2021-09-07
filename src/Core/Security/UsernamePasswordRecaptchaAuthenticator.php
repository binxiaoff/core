<?php

declare(strict_types=1);

namespace KLS\Core\Security;

use JsonException;
use KLS\Core\DTO\GoogleRecaptchaResult;
use KLS\Core\Entity\User;
use KLS\Core\Exception\Authentication\RecaptchaChallengeFailedException;
use KLS\Core\Service\GoogleRecaptchaManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class UsernamePasswordRecaptchaAuthenticator extends AbstractGuardAuthenticator implements PasswordAuthenticatedInterface
{
    public const GOOGLE_RECAPTCHA_RESULT_TOKEN_ATTRIBUTE = 'GOOGLE_RECAPTCHA_RESULT';

    private GoogleRecaptchaManager $googleRecaptchaManager;
    private AuthenticationSuccessHandlerInterface $authenticationSuccessHandler;
    private AuthenticationFailureHandlerInterface $authenticationFailureHandler;
    private UserPasswordEncoderInterface $passwordEncoder;
    private string $path;
    private GoogleRecaptchaResult $recaptchaResult;

    public function __construct(
        GoogleRecaptchaManager $googleRecaptchaManager,
        UserPasswordEncoderInterface $passwordEncoder,
        AuthenticationSuccessHandlerInterface $authenticationSuccessHandler,
        AuthenticationFailureHandlerInterface $authenticationFailureHandler,
        string $path = '/core/authentication_token'
    ) {
        $this->googleRecaptchaManager       = $googleRecaptchaManager;
        $this->authenticationSuccessHandler = $authenticationSuccessHandler;
        $this->authenticationFailureHandler = $authenticationFailureHandler;
        $this->passwordEncoder              = $passwordEncoder;
        $this->path                         = $path;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $exception = new AuthenticationException('Authentication required', 0, $authException);

        return $this->onAuthenticationFailure($request, $exception);
    }

    public function supports(Request $request)
    {
        if ($request->getPathInfo() !== $this->path) {
            return false;
        }

        if (false === \mb_strpos((string) $request->getRequestFormat(), 'json') && false === \mb_strpos((string) $request->getContentType(), 'json')) {
            return false;
        }

        $content = \json_decode($request->getContent(), true, 512);

        if (null === $content) {
            return false;
        }

        return \array_key_exists('username', $content) && \array_key_exists('password', $content) && \array_key_exists('captchaValue', $content);
    }

    public function getCredentials(Request $request)
    {
        try {
            $content = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \UnexpectedValueException('Invalid JSON', 0, $exception);
        }

        return [
            'username'     => $content['username'] ?? null,
            'password'     => $content['password'] ?? null,
            'captchaValue' => $content['captchaValue'] ?? null,
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['username']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $recaptchaResult = $this->googleRecaptchaManager->getResult($credentials['captchaValue']);

        if ($user instanceof User) {
            $user->setRecaptchaResult($recaptchaResult);
        }

        if (false === $recaptchaResult->valid) {
            throw new RecaptchaChallengeFailedException($this->recaptchaResult);
        }

        if (false === $this->passwordEncoder->isPasswordValid($user, $this->getPassword($credentials))) {
            throw new BadCredentialsException('Invalid credentials');
        }

        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->authenticationFailureHandler->onAuthenticationFailure($request, $exception);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        return $this->authenticationSuccessHandler->onAuthenticationSuccess($request, $token);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    public function getPassword($credentials): ?string
    {
        return $credentials['password'] ?? null;
    }
}
