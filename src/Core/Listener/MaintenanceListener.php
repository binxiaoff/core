<?php

declare(strict_types=1);

namespace Unilend\Core\Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class MaintenanceListener
{
    private bool $enabled;

    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($this->enabled) {
            $event->setResponse(
                new Response(
                    'site is in maintenance mode',
                    Response::HTTP_SERVICE_UNAVAILABLE
                )
            );
            $event->stopPropagation();
        }
    }
}
