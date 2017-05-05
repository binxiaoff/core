<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ContactController extends Controller
{
    /**
     * @Route("partenaire/contact", name="partner_contact")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function contactAction()
    {
        return $this->render('/partner_account/contact.html.twig');
    }
}
