<?php

namespace Unilend\Controller\Unilend\PartnerAccount;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @Route("partenaire", name="partner_home")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function homeAction(): Response
    {
        return $this->render('/partner_account/home.html.twig');
    }
}
