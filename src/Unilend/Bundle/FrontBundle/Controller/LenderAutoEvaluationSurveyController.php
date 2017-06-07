<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
use Unilend\core\Loader;

class LenderAutoEvaluationSurveyController extends Controller
{
    const VALUE_TOTAL_ESTATE_THRESHOLD    = 20000;
    const VALUE_MONTHLY_SAVINGS_THRESHOLD = 100;

    /**
     * @Route("/auto-evaluation", name="lender_auto_evaluation")
     *
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('/pages/lender_auto_evaluation_survey.html.twig');
    }

    /**
     * @Route("/auto-evaluation/questionnaire", name="lender_auto_evaluation_survey")
     *
     * @return Response
     */
    public function surveyAction(Request $request)
    {
        $answers               = $this->get('session')->get('answers', []);
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $questionsRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderQuestionnaireQuestion');
        $submittedQuestionType = filter_var($request->request->get('question'), \FILTER_SANITIZE_STRING);
        $nextQuestionType      = \lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE;
        $currentStep           = 1;

        if (\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE == $submittedQuestionType) {
            $amount  = $request->request->getInt('estate-answer');
            $answers = array_merge($answers, [\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE => $amount]);

            if ($amount >= self::VALUE_TOTAL_ESTATE_THRESHOLD) {
                $nextQuestionType = \lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD;
                $currentStep      = 3;
            } else {
                $nextQuestionType = \lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS;
                $currentStep      = 2;
            }
        }

        if (\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS == $submittedQuestionType) {
            $amount = filter_var($request->request->get('savings-answer'), \FILTER_VALIDATE_INT);

            if ($amount < self::VALUE_MONTHLY_SAVINGS_THRESHOLD) {
                return $this->render('/pages/lender_auto_evaluation_survey.html.twig', [
                    'advices' => [$this->get('translator')->trans('lender-auto-evaluation_rejection-message')]
                ]);
            }

            $nextQuestionType = \lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD;
            $currentStep      = 3;
            $answers          = array_merge($answers, [\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS => $amount]);
        }

        if (\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD == $submittedQuestionType) {
            $nextQuestionType = \lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE;
            $currentStep      = 4;
            $answers          = array_merge($answers, [\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD => filter_var($request->request->get('blocking-period-answer'), \FILTER_SANITIZE_STRING)]);
        }

        if (\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE == $submittedQuestionType) {
            return $this->redirectToRoute('lender_auto_evaluation_result');
        }

        $this->get('session')->set('answers', $answers);

        return $this->render('/pages/lender_auto_evaluation_survey.html.twig', [
            'question'    => $questionsRepository->findOneBy(['type' => $nextQuestionType]),
            'currentStep' => $currentStep,
            'answers'     => $answers
        ]);
    }

    /**
     * @Route("/auto-evaluation/resultat", name="lender_auto_evaluation_result")
     *
     * @return Response
     */
    public function evaluationResultAction()
    {
        $answers = $this->get('session')->get('answers', []);
        $this->get('session')->remove('answers');

        if (empty($answers)) {
            return $this->redirectToRoute('lender_auto_evaluation');
        }

        return $this->render('/pages/lender_auto_evaluation_survey.html.twig', [
            'advices' => $this->getAdvices($answers)
        ]);
    }

    /**
     * @param array $answers
     *
     * @return array
     */
    private function getAdvices(array $answers)
    {
        /** @var \ficelle $ficelle */
        $ficelle    = Loader::loadLib('ficelle');
        $translator = $this->get('translator');

        $estate         = $answers[\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE];
        $monthlySavings = isset($answers[\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS]) ? $answers[\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS] : 0;
        $blockingPeriod = $answers[\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD];
        $indicators     = $this->get('unilend.service.cip_manager')->getIndicatorsBasedOnAnswers($estate, $monthlySavings, $blockingPeriod);
        $advices[]      = $this->get('translator')->trans('lender-auto-evaluation_results-default-advice');

        if ($indicators[CIPManager::INDICATOR_TOTAL_AMOUNT] >= 2000 ) {
            if (floor($indicators[CIPManager::INDICATOR_TOTAL_AMOUNT] / 200) <= 20) {
                $advices[] = $translator->trans('lender-auto-evaluation_results-estate-advice', [
                    '%maxAmountEstate%' => $ficelle->formatNumber($indicators[CIPManager::INDICATOR_TOTAL_AMOUNT], 0),
                    '%maxAmount100%'    => $ficelle->formatNumber(floor($indicators[CIPManager::INDICATOR_TOTAL_AMOUNT] / 100), 0)
                ]);
            } else {
                $advices[] = $translator->trans('lender-auto-evaluation_results-estate-advice-loan-variation', [
                    '%maxAmountEstate%' => $ficelle->formatNumber($indicators[CIPManager::INDICATOR_TOTAL_AMOUNT], 0),
                    '%maxAmount100%'    => $ficelle->formatNumber(floor($indicators[CIPManager::INDICATOR_TOTAL_AMOUNT] / 100), 0),
                    '%maxAmount200%'    => $ficelle->formatNumber(floor($indicators[CIPManager::INDICATOR_TOTAL_AMOUNT] / 200), 0)
                ]);
            }
        } else {
            $advices[] = $this->get('translator')->trans('lender-auto-evaluation_results-monthly-investment-advice');
        }

        switch ($indicators[CIPManager::INDICATOR_PROJECT_DURATION]) {
            case CIPManager::INDICATOR_PROJECT_DURATION_1_YEAR:
                $advices[] = $translator->trans('lender-auto-evaluation_results-blocking-period-less-1-year-advice');
                break;
            case CIPManager::INDICATOR_PROJECT_DURATION_3_YEARS:
                $advices[] = $translator->trans('lender-auto-evaluation_results-blocking-period-less-3-years-advice');
                break;
            case CIPManager::INDICATOR_PROJECT_DURATION_5_YEARS:
                $advices[] = $translator->trans('lender-auto-evaluation_results-blocking-period-less-5-years-advice');
                break;
            default:
                $advices[] = $translator->trans('lender-auto-evaluation_results-blocking-period-plus-5-years-advice');
                break;
        }

        return $advices;
    }
}
