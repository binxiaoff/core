<?php

declare(strict_types=1);

namespace Unilend\Security;

use Exception;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\{Exception\AuthenticationException, User\UserInterface, User\UserProviderInterface};
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Unilend\Entity\TemporaryToken;
use Unilend\Event\TemporaryToken\{TemporaryTokenAuthenticationEvents, TemporaryTokenAuthenticationFailureEvent, TemporaryTokenAuthenticationSuccessEvent};
use Unilend\Exception\TemporaryToken\TemporaryTokenInvalidException;
use Unilend\Repository\TemporaryTokenRepository;

class TemporaryTokenAuthenticator extends AbstractGuardAuthenticator
{
    private const SUPPORTED_PATH_PATTERN = '/^\/temporary_tokens\/([a-z0-9]{32})\//';
    /** @var TemporaryTokenRepository */
    private $temporaryTokenRepository;
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * @param TemporaryTokenRepository $temporaryTokenRepository
     * @param EventDispatcherInterface $dispatcher
     */
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
        $exception = new TemporaryTokenInvalidException('Temporary Token not found.', 0, $authException);
        $event     = new TemporaryTokenAuthenticationFailureEvent($exception, $this->buildAuthenticationFailureResponse($exception->getMessageKey()));
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_FAILURE);

        return $event->getResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        return 1 === preg_match(self::SUPPORTED_PATH_PATTERN, $request->getPathInfo());
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        preg_match(self::SUPPORTED_PATH_PATTERN, $request->getPathInfo(), $matches);

        $temporaryToken = $this->temporaryTokenRepository->findOneBy(['token' => $matches[1]]);

        if (null === $temporaryToken) {
            throw new TemporaryTokenInvalidException('Temporary Token not found.');
        }

        return $temporaryToken;
    }

    /**
     * {@inheritdoc}
     *
     * @param TemporaryToken $temporaryToken
     */
    public function getUser($temporaryToken, UserProviderInterface $userProvider)
    {
        return $temporaryToken->getClient();
    }

    /**
     * {@inheritdoc}
     *
     * @param TemporaryToken $temporaryToken
     *
     * @throws Exception
     */
    public function checkCredentials($temporaryToken, UserInterface $user): bool
    {
        if (false === $temporaryToken instanceof TemporaryToken || false === $temporaryToken->isValid()) {
            throw new TemporaryTokenInvalidException('Temporary token is not valid.');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $authException): Response
    {
        $event = new TemporaryTokenAuthenticationFailureEvent($authException, $this->buildAuthenticationFailureResponse($authException->getMessageKey()));
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_FAILURE);

        return $event->getResponse();
    }

    /**
     * {@inheritdoc}
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

    /**
     * @param string $message
     *
     * @return JsonResponse
     */
    private function buildAuthenticationFailureResponse(string $message): JsonResponse
    {
        return new JsonResponse(['code' => JsonResponse::HTTP_UNAUTHORIZED, 'message' => $message], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
