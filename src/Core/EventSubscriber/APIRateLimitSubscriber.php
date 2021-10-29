<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

class APIRateLimitSubscriber implements EventSubscriberInterface
{
    private const ANONYMOUS_API_ENDPOINTS = [
        '/core/reset_passwords',
    ];

    private RateLimiterFactory  $anonymousApiLimiter;
    private TranslatorInterface $translator;

    public function __construct(RateLimiterFactory $anonymousApiLimiter, TranslatorInterface $translator)
    {
        $this->anonymousApiLimiter = $anonymousApiLimiter;
        $this->translator          = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'checkRateLimit',
        ];
    }

    public function checkRateLimit(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        if (false === \in_array($path, self::ANONYMOUS_API_ENDPOINTS, true)) {
            return;
        }

        $limit = $this->anonymousApiLimiter->create($path . '-' . $request->getClientIp())->consume();
        if (false === $limit->isAccepted()) {
            $retryAfter             = $limit->getRetryAfter()->getTimestamp() - (new \DateTime())->getTimestamp();
            $headers['Retry-After'] = $retryAfter;
            $event->setResponse(new JsonResponse([
                'code'    => 429,
                'message' => $this->translator->trans(
                    'rate-limit.too-many-calls',
                    ['%minutes%' => \ceil($retryAfter / 60)]
                ),
            ], 429, $headers));
        }
    }
}
