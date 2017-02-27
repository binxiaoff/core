<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;

class PaylineController extends Controller
{
    /**
     * @Route("/notification_payline", name="payline_callback")
     *
     */
    public function callbackAction(Request $request)
    {
        $token = $request->get('token');
        if (null === $token) {
            return new Response();
        }

        $this->get('unilend.service.payline_manager')->handlePaylineReturn($token, Backpayline::WS_DEFAULT_VERSION);

        return new Response();
    }
}
