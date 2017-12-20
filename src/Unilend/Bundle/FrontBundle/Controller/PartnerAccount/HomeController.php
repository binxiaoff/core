<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\{Route, Security};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
    /**
     * @Route("partenaire", name="partner_home")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function homeAction()
    {
        return $this->render('/partner_account/home.html.twig');
    }
}
