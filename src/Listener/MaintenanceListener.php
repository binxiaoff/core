<?php

namespace Unilend\Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class MaintenanceListener
{

    /**
     * @var bool
     */
    private bool $enabled;

    /**
     * @param bool $enabled
     */
    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param RequestEvent $event
     */
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
