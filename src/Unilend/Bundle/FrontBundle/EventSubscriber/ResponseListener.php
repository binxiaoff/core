<?php

namespace Unilend\Bundle\FrontBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $responseHeaders = $event->getResponse()->headers;
        $responseHeaders->set('X-Server', exec('hostname'));

        if ('lender_landing_page_form_only' !== $event->getRequest()->attributes->get('_route')) {
            $responseHeaders->set('X-Frame-Options', 'DENY');
            $responseHeaders->set('X-Frame-Options', 'ALLOW-FROM http://app.vwo.com/');
        }
    }
}
