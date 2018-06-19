<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{
    Request, Response
};

class LenderAutoEvaluationController extends Controller
{
    const VALUE_TOTAL_ESTATE_THRESHOLD    = 20000;
    const VALUE_MONTHLY_SAVINGS_THRESHOLD = 100;

    const TYPE_VALUE_YEARLY_EARNINGS = 'value-yearly-earnings';
    const TYPE_VALUE_YEARLY_COSTS    = 'value-yearly-costs';

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
        $submittedQuestionType = filter_var($request->request->get('question'), \FILTER_SANITIZE_STRING);
        $nextTypeValue         = \lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE;
        $currentStep           = $step;

        if ($request->request->has('estate-answer')) {
            $estateAmount = $request->request->getInt('estate-answer');
            $answers      = array_merge($answers, [\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE => $estateAmount]);
        }

        if (\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE == $submittedQuestionType) {
            $nextTypeValue = self::TYPE_VALUE_YEARLY_EARNINGS;
            $currentStep   = 2;
        }

        if ($yearlyEarnings = $request->request->getInt('yearly-earnings-answer')) {
            $answers = array_merge($answers, [self::TYPE_VALUE_YEARLY_EARNINGS => $yearlyEarnings]);
        }

        if (self::TYPE_VALUE_YEARLY_EARNINGS == $submittedQuestionType ) {
            $nextTypeValue = self::TYPE_VALUE_YEARLY_COSTS;
            $currentStep   = 3;
        }

        if ($yearlyCosts = $request->request->getInt('yearly-costs-answer')) {
            $answers = array_merge($answers, [self::TYPE_VALUE_YEARLY_COSTS => $yearlyCosts]);
        }

        if (self::TYPE_VALUE_YEARLY_COSTS == $submittedQuestionType) {
            $nextTypeValue = \lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD;
            $currentStep   = 4;

            if (
                $answers[\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE] < self::VALUE_TOTAL_ESTATE_THRESHOLD
                && round(bcdiv(bcsub($yearlyEarnings, $yearlyCosts, 4), 12, 4)) < self::VALUE_MONTHLY_SAVINGS_THRESHOLD
            ) {
                return $this->render('lender_auto_evaluation/survey.html.twig', [
                    'advices' => [$this->get('translator')->trans('lender-auto-evaluation_rejection-message')]
                ]);
            }
        }

        if ($blockingPeriod = filter_var($request->request->get('blocking-period-answer'), FILTER_SANITIZE_STRING)) {
            $answers = array_merge($answers, [\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD => $blockingPeriod]);
        }

        $this->get('session')->set('answers', $answers);

        if (\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD == $submittedQuestionType) {
            return $this->redirectToRoute('lender_auto_evaluation_result');
        }

        return $this->render('lender_auto_evaluation/survey.html.twig', [
            'question'    => $nextTypeValue,
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
        $numberFormatter = $this->get('number_formatter');
        $translator      = $this->get('translator');

        $estate         = $answers[\lender_questionnaire_question::TYPE_VALUE_TOTAL_ESTATE];
        $yearlyEarnings = $answers[self::TYPE_VALUE_YEARLY_EARNINGS];
        $yearlyCosts    = $answers[self::TYPE_VALUE_YEARLY_COSTS];
        $blockingPeriod = $answers[\lender_questionnaire_question::TYPE_VALUE_BLOCKING_PERIOD];
        $advices[]      = $this->get('translator')->trans('lender-auto-evaluation_results-default-advice');

        $availableMoney = bcadd($estate, $yearlyEarnings, 4);
        $afterCosts     = bcsub($availableMoney, $yearlyCosts, 4);
        $amountToInvest = round(bcmul($afterCosts, 0.1, 4));

        if ($amountToInvest >= 2000 ) {
            if (floor($amountToInvest / 200) <= 20) {
                $advices[] = $translator->trans('lender-auto-evaluation_results-estate-advice', [
                    '%maxAmountEstate%' => $numberFormatter->format($amountToInvest, 0),
                    '%maxAmount100%'    => $numberFormatter->format(floor($amountToInvest / 100), 0)
                ]);
            } else {
                $advices[] = $translator->trans('lender-auto-evaluation_results-estate-advice-loan-variation', [
                    '%maxAmountEstate%' => $numberFormatter->format($amountToInvest, 0),
                    '%maxAmount100%'    => $numberFormatter->format(floor($amountToInvest / 100), 0),
                    '%maxAmount200%'    => $numberFormatter->format(floor($amountToInvest / 200), 0)
                ]);
            }
        } else {
            $advices[] = $this->get('translator')->trans('lender-auto-evaluation_results-monthly-investment-advice');
        }

        switch ($blockingPeriod) {
            case \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_1:
                $advices[] = $translator->trans('lender-auto-evaluation_results-blocking-period-less-1-year-advice');
                break;
            case \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_2:
                $advices[] = $translator->trans('lender-auto-evaluation_results-blocking-period-less-3-years-advice');
                break;
            case \lender_questionnaire_question::VALUE_BLOCKING_PERIOD_3:
                $advices[] = $translator->trans('lender-auto-evaluation_results-blocking-period-less-5-years-advice');
                break;
            default:
                $advices[] = $translator->trans('lender-auto-evaluation_results-blocking-period-plus-5-years-advice');
                break;
        }

        return $advices;
    }
}
