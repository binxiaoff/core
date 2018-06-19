<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
use Unilend\core\Loader;

class LenderAutoEvaluationController extends Controller
{
    const VALUE_TOTAL_ESTATE_THRESHOLD    = 20000;
    const VALUE_MONTHLY_SAVINGS_THRESHOLD = 100;

    /**
     * @Route("/auto-evaluation", name="lender_auto_evaluation")
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        $this->get('session')->remove('answers');
        return $this->render('lender_auto_evaluation/survey.html.twig');
    }

    /**
     * @Route("/auto-evaluation/questionnaire/{step}", name="lender_auto_evaluation_survey")
     *
     * @param int     $step
     * @param Request $request
     *
     * @return Response
     */
    public function surveyAction(int $step, Request $request): Response
    {
        if (empty($this->get('session')->get('answers')) && $step > 1) {
            return $this->redirectToRoute('lender_auto_evaluation_survey', ['step' => 1]);
        }
        $answers               = $this->get('session')->get('answers', []);
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $questionsRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:LenderQuestionnaireQuestion');
        $submittedQuestionType = filter_var($request->request->get('question'), \FILTER_SANITIZE_STRING);
        $nextQuestionType      = \lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE;
        $currentStep           = $step;

        if ($estateAmount = $request->request->getInt('estate-answer')) {
            $answers = array_merge($answers, [\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE => $estateAmount]);
        }
        if (\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE == $submittedQuestionType) {
            if ($answers[\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE] >= self::VALUE_TOTAL_ESTATE_THRESHOLD) {
                $nextQuestionType = \lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD;
                $currentStep      = 3;
            } else {
                $nextQuestionType = \lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS;
                $currentStep      = 2;
            }
        }

        if ($savingAmount = filter_var($request->request->getInt('savings-answer'), FILTER_VALIDATE_INT)) {
            $answers = array_merge($answers, [\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS => $savingAmount]);
        }
        if (\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS == $submittedQuestionType) {

            if ($answers[\lender_questionnaire_question::TYPE_VALUE_MONTHLY_SAVINGS] < self::VALUE_MONTHLY_SAVINGS_THRESHOLD) {
                return $this->render('lender_auto_evaluation/survey.html.twig', [
                    'advices' => [$this->get('translator')->trans('lender-auto-evaluation_rejection-message')]
                ]);
            }
            $nextQuestionType = \lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD;
            $currentStep      = 3;
        }

        if ($blockingPeriod = filter_var($request->request->get('blocking-period-answer'), FILTER_SANITIZE_STRING)) {
            $answers = array_merge($answers, [\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD => $blockingPeriod]);
        }
        if (\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD == $submittedQuestionType) {
            $nextQuestionType = \lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE;
            $currentStep      = 4;
        }

        if (\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE == $submittedQuestionType) {
            return $this->redirectToRoute('lender_auto_evaluation_result');
        }

        $this->get('session')->set('answers', $answers);

        return $this->render('lender_auto_evaluation/survey.html.twig', [
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

        return $this->render('lender_auto_evaluation/survey.html.twig', [
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
