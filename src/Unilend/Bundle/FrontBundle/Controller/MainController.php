<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;
use Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\FrontBundle\Service\TestimonialManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;


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
        $testimonialService = $this->get('unilend.service.testimonial_manager');
        /** @var ProjectManager $projectManager */
        $projectManager     = $this->get('unilend.service.project_manager');
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        $aRateRange                              = array(\bids::BID_RATE_MIN, \bids::BID_RATE_MAX);
        $aTemplateVariables['projects']          = $projectManager->getProjectsForDisplay(array(\projects_status::EN_FUNDING), 'p.date_retrait_full ASC', $aRateRange);
        $aTemplateVariables['testimonialPeople'] = $testimonialService->getActiveBattenbergTestimonials();
        $aTemplateVariables['videoHeroes']       = [
            'Lenders'   => $testimonialService->getActiveVideoHeroes('preter'),
            'Borrowers' => $testimonialService->getActiveVideoHeroes('emprunter')
        ];
        $aTemplateVariables['showWelcomeOffer']  = $welcomeOfferManager->displayOfferOnHome();
        $aTemplateVariables['loanPeriods']       = $projectManager->getPossibleProjectPeriods();
        $aTemplateVariables['borrowingMotives']  = $translationManager->getTranslatedBorrowingMotiveList();

        //TODO replace switch by cookie check
        switch($type) {
            case 'lender' :
                $sTemplateToRender = 'pages/homepage_lender.html.twig';
                break;
            case 'borrower' :
                $sTemplateToRender = 'pages/homepage_borrower.html.twig';
                break;
            case 'acquisition':
            default:
                $sTemplateToRender = 'pages/homepage_acquisition.html.twig';
                break;
        };

        return $this->render($sTemplateToRender, $aTemplateVariables);
    }

    /**
     * @Route("/esim-step-1", name="esim_step_1")
     * @Method("POST")
     */
    public function projectSimulatorStepOneAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {

            $period   = $request->request->get('period');
            $amount   = $request->request->get('amount');
            $motiveId = $request->request->get('motiveId');

            /** @var ProjectRequestManager $projectRequestManager */
            $projectRequestManager = $this->get('unilend.service.project_request_manager');
            /** @var ProjectManager $projectManager */
            $projectManager        = $this->get('unilend.service.project_manager');
            /** @var TranslationManager $translationManager */
            $translationManager = $this->get('unilend.service.translation_manager');

            $aProjectPeriods   = $projectManager->getPossibleProjectPeriods();
            $iProjectAmountMax = $projectManager->getMaxProjectAmount();
            $iProjectAmountMin = $projectManager->getMinProjectAmount();

            if (in_array($period, $aProjectPeriods)
                && $amount >= $iProjectAmountMin
                && $amount <= $iProjectAmountMax
            ){
                $estimatedRate                  = $projectRequestManager->getMonthlyRateEstimate();
                $estimatedMonthlyRepayment      = $projectRequestManager->getMonthlyPaymentEstimate($amount, $period, $estimatedRate);
                $sTranslationComplement         = $translationManager->selectTranslation('home-borrower', 'simulator-step-2-text-segment-motive-' . $motiveId);
                $bMotiveComplementToBeDisplayed = (7 === $motiveId) ? false : true; //TODO constant once table has been renamed in projectMotive

                return new JsonResponse(array(
                    'estimatedRate'                 => $estimatedRate,
                    'estimatedMonthlyRepayment'     => $estimatedMonthlyRepayment,
                    'translationComplement'         => $sTranslationComplement,
                    'motiveComplementToBeDisplayed' => $bMotiveComplementToBeDisplayed,
                    'period'                        => $period,
                    'amount'                        => $amount
                ));
            }

            return new JsonResponse('nok');
        }
        return new Response('not an ajax request');
    }

    /**
     * @Route("/esim-step-2", name="esim_step_2")
     * @Method("POST")
     */
    public function projectSimulatorStepTwoAction(Request $request)
    {
        $aFormData = $request->request->get('esim');

        /** @var ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $projectRequestManager->saveSimulatorRequest($aFormData);

        $session   = $request->getSession();
        $session->set('SimulatorData', $aFormData);

        return $this->redirectToRoute('project_request_step_1');
    }

}