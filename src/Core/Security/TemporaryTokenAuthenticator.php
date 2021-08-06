<?php

declare(strict_types=1);

namespace Unilend\Core\Security;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Unilend\Core\Event\TemporaryToken\TemporaryTokenAuthenticationEvents;
use Unilend\Core\Event\TemporaryToken\TemporaryTokenAuthenticationFailureEvent;
use Unilend\Core\Event\TemporaryToken\TemporaryTokenAuthenticationSuccessEvent;
use Unilend\Core\Exception\TemporaryToken\InvalidTemporaryTokenException;
use Unilend\Core\Repository\TemporaryTokenRepository;

class TemporaryTokenAuthenticator extends AbstractGuardAuthenticator
{
    private TemporaryTokenRepository $temporaryTokenRepository;

    private EventDispatcherInterface $dispatcher;

    public function __construct(TemporaryTokenRepository $temporaryTokenRepository, EventDispatcherInterface $dispatcher)
    {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->dispatcher               = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $exception = new InvalidTemporaryTokenException('Temporary token is not found.', 0, $authException);
        $event     = new TemporaryTokenAuthenticationFailureEvent($exception, $this->buildAuthenticationFailureResponse($exception->getMessage()));
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_FAILURE);

        return $event->getResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        return $request->headers->get('X-AUTH-TOKEN');
    }

    /**
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $authException): Response
    {
        $event = new TemporaryTokenAuthenticationFailureEvent($authException, $this->buildAuthenticationFailureResponse($authException->getMessage()));
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_FAILURE);

        return $event->getResponse();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $event = new TemporaryTokenAuthenticationSuccessEvent($token->getUser());
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_SUCCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function buildAuthenticationFailureResponse(string $message): JsonResponse
    {
        return new JsonResponse(['code' => JsonResponse::HTTP_UNAUTHORIZED, 'message' => $message], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
