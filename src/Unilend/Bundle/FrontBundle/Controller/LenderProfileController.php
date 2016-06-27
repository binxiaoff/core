<?php


namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class LenderProfileController extends Controller
{

    /**
     * @Route("/profile/synthese", name="lender_dashboard")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showDashboardAction()
    {

        return $this->render('pages/user_preter_dashboard.twig',
            array()
        );
    }

    /**
     * @Route("/profile/documents", name="lender_completeness")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function showLenderCompletenessForm()
    {
        return $this->render('Ici viendra le formulaire d\'upload des fichiers de complétude');
    }

}