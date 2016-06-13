<?php
/**
 * Created by PhpStorm.
 * User: annabreyer
 * Date: 13/06/2016
 * Time: 17:31
 */

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class LenderProfileController extends Controller
{

    /**
     * @Route("/profile/synthese", name="lender_dashboard")
     */
    public function showDashboardAction()
    {
        $this->render('UnilendFrontBundle:pages:user_preter_dashboard.twig',
            array()
        );
    }

}