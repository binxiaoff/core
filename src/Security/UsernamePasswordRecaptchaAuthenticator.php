<?php

declare(strict_types=1);

namespace Unilend\Security;

use JsonException;
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
use Unilend\Exception\Authentication\RecaptchaChallengeFailedException;
use Unilend\Service\GoogleRecaptchaManager;

class UsernamePasswordRecaptchaAuthenticator extends AbstractGuardAuthenticator implements PasswordAuthenticatedInterface
{
    private GoogleRecaptchaManager $googleRecaptchaManager;

    private AuthenticationSuccessHandlerInterface $authenticationSuccessHandler;

    private AuthenticationFailureHandlerInterface $authenticationFailureHandler;

    private UserPasswordEncoderInterface $passwordEncoder;

    /**
     * @param GoogleRecaptchaManager                $googleRecaptchaManager
     * @param UserPasswordEncoderInterface          $passwordEncoder
     * @param AuthenticationSuccessHandlerInterface $authenticationSuccessHandler
     * @param AuthenticationFailureHandlerInterface $authenticationFailureHandler
     */
    public function __construct(
        GoogleRecaptchaManager $googleRecaptchaManager,
        UserPasswordEncoderInterface $passwordEncoder,
        AuthenticationSuccessHandlerInterface $authenticationSuccessHandler,
        AuthenticationFailureHandlerInterface $authenticationFailureHandler
    ) {
        $this->googleRecaptchaManager       = $googleRecaptchaManager;
        $this->authenticationSuccessHandler = $authenticationSuccessHandler;
        $this->authenticationFailureHandler = $authenticationFailureHandler;
        $this->passwordEncoder              = $passwordEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $exception = new AuthenticationException('Authentication required', 0, $authException);

        return $this->onAuthenticationFailure($request, $exception);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        if (false === mb_strpos((string) $request->getRequestFormat(), 'json') && false === mb_strpos((string) $request->getContentType(), 'json')) {
            return false;
        }

        $content = json_decode($request->getContent(), true, 512);

        if (null === $content) {
            return false;
        }

        return \array_key_exists('username', $content) && \array_key_exists('password', $content) && \array_key_exists('captchaValue', $content);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        try {
            $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \UnexpectedValueException('Invalid JSON', 0, $exception);
        }

        return [
            'username'     => $content['username'] ?? null,
            'password'     => $content['password'] ?? null,
            'captchaValue' => $content['captchaValue'] ?? null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['username']);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if (false === $this->googleRecaptchaManager->isValid($credentials['captchaValue'])) {
            throw new RecaptchaChallengeFailedException();
        }

        if (false === $this->passwordEncoder->isPasswordValid($user, $this->getPassword($credentials))) {
            throw new BadCredentialsException('Invalid credentials');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->authenticationFailureHandler->onAuthenticationFailure($request, $exception);
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey)
    {
        return $this->authenticationSuccessHandler->onAuthenticationSuccess($request, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }
}
