<?php

declare(strict_types=1);

namespace KLS\Core\EventSubscriber;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class TotalCountHeaderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => 'addTotalCountHeader',
        ];
    }

    public function addTotalCountHeader(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $data    = $request->attributes->get('data');

        if (false === ($data instanceof PaginatorInterface)) {
            return;
        }

        $response = $event->getResponse();

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return;
        }

        $response->headers->set('Total-Count', $data->getTotalItems());
    }
}
