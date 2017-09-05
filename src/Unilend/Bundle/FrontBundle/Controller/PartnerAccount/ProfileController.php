<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\FrontBundle\Form\PartnerContactType;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;

class ProfileController extends Controller
{
    /**
     * @Route("partenaire/profil", name="partner_profile")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function profileAction(Request $request)
    {
        return $this->render('/partner_account/profile.html.twig');
    }
}
