<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class PartnerAccountController extends Controller
{
    /**
     * @Route("partenaire/depot", name="partner_project_request")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestAction()
    {
        return $this->render('/partner_account/project_request.html.twig');
    }

    /**
     * @Route("partenaire/depot/details", name="partner_project_success_request")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestResultAction()
    {
        return $this->render('/partner_account/project_request_details.html.twig');
    }

    /**
     * @Route("partenaire/emprunteurs", name="partner_projects_list")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectsListAction()
    {
        return $this->render('/partner_account/projects_list.html.twig');
    }

    /**
     * @Route("partenaire/utilisateurs", name="partner_users")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @return Response
     */
    public function usersAction()
    {
        return $this->render('/partner_account/users.html.twig');
    }

    /**
     * @Route("partenaire/performance", name="partner_statistics")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function statisticsAction()
    {
        return $this->render('/partner_account/statistics.html.twig');
    }

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
