<?php

namespace Unilend\Bundle\FrontBundle\Controller\Endpoint;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\Backpayline;

class PaylineController extends Controller
{
    /**
     * @Route("/ws/payment/payline/notify", name="payline_callback")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function callbackAction(Request $request): Response
    {
        $token = $request->get('token');
        if (null === $token) {
            return new Response();
        }

        $this->get('unilend.service.payline_manager')->handleResponse($token, Backpayline::WS_DEFAULT_VERSION);

        return new Response();
    }
}
