<?php

declare(strict_types=1);

namespace KLS\Core\Security;

use Exception;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationEvents;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationFailureEvent;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationSuccessEvent;
use KLS\Core\Exception\TemporaryToken\InvalidTemporaryTokenException;
use KLS\Core\Repository\TemporaryTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TemporaryTokenAuthenticator extends AbstractGuardAuthenticator
{
    private TemporaryTokenRepository $temporaryTokenRepository;
    private EventDispatcherInterface $dispatcher;

    public function __construct(TemporaryTokenRepository $temporaryTokenRepository, EventDispatcherInterface $dispatcher)
    {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->dispatcher               = $dispatcher;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $exception = new InvalidTemporaryTokenException('Temporary token is not found.', 0, $authException);
        $event     = new TemporaryTokenAuthenticationFailureEvent($exception, $this->buildAuthenticationFailureResponse($exception->getMessage()));
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_FAILURE);

        return $event->getResponse();
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    public function getCredentials(Request $request)
    {
        return $request->headers->get('X-AUTH-TOKEN');
    }

    /**
     * @param mixed $credentials
     *
     * @throws Exception
     */
    public function getUser($credentials, UserProviderInterface $userProvider): UserInterface
    {
        if (empty($credentials)) {
            throw new InvalidTemporaryTokenException('Temporary token is empty.');
        }

        $temporaryToken = $this->temporaryTokenRepository->findOneBy(['token' => $credentials]);

        if (null === $temporaryToken) {
            throw new InvalidTemporaryTokenException('Temporary token is not found.');
        }

        if (false === $temporaryToken->isValid()) {
            throw new InvalidTemporaryTokenException('Temporary token is not valid.');
        }

        $temporaryToken->setAccessed();
        $this->temporaryTokenRepository->save($temporaryToken);

        return $temporaryToken->getUser();
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $authException): Response
    {
        $event = new TemporaryTokenAuthenticationFailureEvent($authException, $this->buildAuthenticationFailureResponse($authException->getMessage()));
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_FAILURE);

        return $event->getResponse();
    }

    /**
     * @param mixed $providerKey
     *
     * @throws Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $event = new TemporaryTokenAuthenticationSuccessEvent($token->getUser());
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_SUCCESS);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function buildAuthenticationFailureResponse(string $message): JsonResponse
    {
        return new JsonResponse(['code' => JsonResponse::HTTP_UNAUTHORIZED, 'message' => $message], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
