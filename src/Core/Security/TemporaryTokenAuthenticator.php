<?php

declare(strict_types=1);

namespace KLS\Core\Security;

use Exception;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationEvents;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationFailureEvent;
use KLS\Core\Event\TemporaryToken\TemporaryTokenAuthenticationSuccessEvent;
use KLS\Core\Repository\TemporaryTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TemporaryTokenAuthenticator extends AbstractAuthenticator
{
    private TemporaryTokenRepository $temporaryTokenRepository;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        TemporaryTokenRepository $temporaryTokenRepository,
        EventDispatcherInterface $dispatcher
    ) {
        $this->temporaryTokenRepository = $temporaryTokenRepository;
        $this->dispatcher               = $dispatcher;
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    public function authenticate(Request $request): PassportInterface
    {
        $token = $request->headers->get('X-AUTH-TOKEN');

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('Temporary token is empty.');
        }

        return new SelfValidatingPassport(new UserBadge($token, [$this, 'getUser']));
    }

    /**
     * @throws Exception
     */
    public function getUser(string $token): UserInterface
    {
        $temporaryToken = $this->temporaryTokenRepository->findOneBy(['token' => $token]);

        if (null === $temporaryToken) {
            throw new CustomUserMessageAuthenticationException('Temporary token is not found.');
        }

        if (false === $temporaryToken->isValid()) {
            throw new CustomUserMessageAuthenticationException('Temporary token is not valid.');
        }

        $temporaryToken->setAccessed();
        $this->temporaryTokenRepository->save($temporaryToken);

        return $temporaryToken->getUser();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $response = new JsonResponse(
            [
                'code'    => Response::HTTP_UNAUTHORIZED,
                'message' => \strtr($exception->getMessageKey(), $exception->getMessageData()),
            ],
            Response::HTTP_UNAUTHORIZED
        );
        $event = new TemporaryTokenAuthenticationFailureEvent($exception, $response);
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_FAILURE);

        return $response;
    }

    /**
     * @throws Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $event = new TemporaryTokenAuthenticationSuccessEvent($token->getUser());
        $this->dispatcher->dispatch($event, TemporaryTokenAuthenticationEvents::AUTHENTICATION_SUCCESS);

        return null;
    }
}
