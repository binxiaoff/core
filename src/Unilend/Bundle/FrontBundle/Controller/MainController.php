<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\FrontBundle\Service\TestimonialManager;


class MainController extends Controller
{

    /**
     * @Route("/home/{type}", defaults={"type" = "acquisition"}, name="home")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction($type)
    {
        $aTemplateVariables = array();

        /** @var TestimonialManager $testimonialService */
        $testimonialService    = $this->get('unilend.service.testimonial');
        /** @var ProjectManager $projectManager */
        $projectManager     = $this->get('unilend.service.project_manager');
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');

        $aRateRange = array(\bids::BID_RATE_MIN, \bids::BID_RATE_MAX);
        $aTemplateVariables['projects'] = $projectManager->getProjectsForDisplay(array(\projects_status::EN_FUNDING), 'p.date_retrait_full ASC', $aRateRange);
        $aTemplateVariables['testimonialPeople'] = $testimonialService->getActiveBattenbergTestimonials();
        $aTemplateVariables['videoHeroes'] = [
            'Lenders'   => $testimonialService->getActiveVideoHeroes('preter'),
            'Borrowers' => $testimonialService->getActiveVideoHeroes('emprunter')
        ];
        $aTemplateVariables['showWelcomeOffer'] = $welcomeOfferManager->displayOfferOnHome();
        $aTemplateVariables['loanPeriods'] = $projectManager->getPossibleLoanPeriods();

        //TODO replace switch by cookie check
        switch($type) {
            case 'lender' :
                $sTemplateToRender = 'UnilendFrontBundle:pages:homepage_preter.html.twig';
                break;
            case 'borrower' :
                $sTemplateToRender = 'UnilendFrontBundle:pages:homepage_emprunter.html.twig';
                break;
            case 'acquisition':
            default:
                $sTemplateToRender = 'UnilendFrontBundle:pages:homepage_acquisition.html.twig';
                break;
        };

        return $this->render($sTemplateToRender, $aTemplateVariables);
    }







}