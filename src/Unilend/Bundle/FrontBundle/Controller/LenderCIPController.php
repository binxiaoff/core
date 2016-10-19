<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

class LenderCIPController extends Controller
{
    const TOTAL_QUESTIONNAIRE_STEPS = 10;

    /**
     * @Route("/conseil-cip", name="cip_index")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $lender     = $this->getLenderAccount();
        $template   = [];

        $evaluation = $cipManager->getCurrentEvaluation($lender);

        if (null === $evaluation) {
            $template['evaluation'] = false;
        } elseif (false === $cipManager->isValidEvaluation($evaluation)) {
            $template['evaluation'] = true;
        } else {
            $template['evaluation'] = true;
            $template['advices']    = implode("\n", $cipManager->getAdvices($lender));
        }

        return $this->render('lender_cip/index.html.twig', $template);
    }

    /**
     * @Route("/conseil-cip/commencer", name="cip_start_questionnaire")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startQuestionnaireAction(Request $request)
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $lender     = $this->getLenderAccount();
        $evaluation = $cipManager->getCurrentEvaluation($lender);

        if (null !== $evaluation && count($cipManager->getAnswers($lender)) > 0) {
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        if (null !== $request->query->get('start')) {
            $cipManager->startEvaluation($lender);
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        return $this->render('lender_cip/start.html.twig', ['current_step' => 1, 'total_steps' => self::TOTAL_QUESTIONNAIRE_STEPS]);
    }

    /**
     * @Route("/conseil-cip/questionnaire", name="cip_continue_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function questionnaireAction()
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $lender     = $this->getLenderAccount();
        $evaluation = $cipManager->getCurrentEvaluation($lender);
        $template   = [
            'total_steps' => self::TOTAL_QUESTIONNAIRE_STEPS
        ];

        if (null === $evaluation) {
            return $this->redirectToRoute('cip_start_questionnaire');
        }

        $question = $cipManager->getLastQuestion($lender);

        if (null === $question) {
            $advices = implode("\n", $cipManager->getAdvices($lender));

            $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_ADVICE, $advices);

            $template['advices']         = $advices;
            $template['validEvaluation'] = $cipManager->isValidEvaluation($evaluation);
            return $this->render('lender_cip/advice.html.twig', $template);
        } else {
            $template['current_step'] = $question->order + 1;
            $template['answers']      = [];
            $template['question']     = [
                'id'   => $question->id_lender_questionnaire_question,
                'type' => $question->type,
            ];

            $answers = $cipManager->getAnswersByType($lender);
            $answer  = $answers[$question->type];

            if ('' !== $answer['first_answer']) {
                $template['answers']['first'] = $answer['first_answer'];
            }

            if ('' !== $answer['second_answer']) {
                $template['answers']['second'] = $answer['second_answer'];
            }

            return $this->render('lender_cip/question-' . $question->type . '.html.twig', $template);
        }
    }

    /**
     * @Route("/conseil-cip/questionnaire/form", name="cip_form_questionnaire")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function questionnaireFormAction(Request $request)
    {
        $questionId    = $request->request->get('question');
        $answerValue   = $request->request->get('answer');
        $continueValue = $request->request->get('continue');

        if (null === $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $entityManager = $this->get('unilend.service.entity_manager');

        if (null === $answerValue && null === $continueValue) {
            /** @var \lender_questionnaire_question $question */
            $question = $entityManager->getRepository('lender_questionnaire_question');
            $question->get(\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE, 'type');

            if ($questionId != $question->id_lender_questionnaire_question) {
                // @todo error message
                return $this->redirectToRoute('cip_continue_questionnaire');
            }
        }

        $cipManager = $this->get('unilend.service.cip_manager');
        $lender     = $this->getLenderAccount();
        $evaluation = $cipManager->getCurrentEvaluation($lender);

        if (null === $evaluation) {
            // @todo error message
            return $this->redirectToRoute('cip_start_questionnaire');
        }

        $question = $cipManager->getLastQuestion($lender);

        if ($question->id_lender_questionnaire_question != $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        if (null !== $continueValue) {
            switch ($continueValue) {
                case 'next_question':
                    $cipManager->insertQuestion($lender, $cipManager->getNextQuestion($question));
                    return $this->redirectToRoute('cip_continue_questionnaire');
                case 'loop':
                    $answer = $cipManager->getAnswer($lender, $question, $evaluation);
                    if (null !== $answer) {
                        $answer->status = \lender_evaluation_answer::STATUS_INACTIVE;
                        $answer->update();
                    }
                    $cipManager->insertQuestion($lender, $question);
                    return $this->redirectToRoute('cip_continue_questionnaire');
                case 'back':
                    $bid = $request->getSession()->get('cipBid');

                    if (false === empty($bid['project'])) {
                        /** @var \projects $project */
                        $project = $entityManager->getRepository('projects');

                        if (is_numeric($bid['project']) && $project->get($bid['project'])) {
                            return $this->redirectToRoute('project_detail', ['projectId' => $project->id_project]);
                        }

                        $request->getSession()->remove('cipBid');
                    }

                    return $this->redirectToRoute('cip_index');
            }
        } elseif (null !== $answerValue) {
            $cipManager->saveAnswer($question, $evaluation, $answerValue);
        }

        return $this->redirectToRoute('cip_continue_questionnaire');
    }

    /**
     * @Route("/conseil-cip/valider", name="cip_validate_questionnaire")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function validateQuestionnaireAction(Request $request)
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $cipManager->validateEvaluation($this->getLenderAccount());

        $bid = $request->getSession()->get('cipBid');

        if (false === empty($bid['project'])) {
            /** @var \projects $project */
            $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

            if (is_numeric($bid['project']) && $project->get($bid['project'])) {
                $bid['validate'] = true;
                $request->getSession()->set('cipBid', $bid);
                return $this->redirectToRoute('place_bid', ['projectId' => $project->id_project]);
            }

            $request->getSession()->remove('cipBid');
        }

        return $this->redirectToRoute('cip_index');
    }

    /**
     * @Route("/conseil-cip/reset", name="cip_reset_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetQuestionnaireAction()
    {
        $cipManager = $this->get('unilend.service.cip_manager');
        $cipManager->endCurrentEvaluation($this->getLenderAccount());

        return $this->redirectToRoute('cip_start_questionnaire');
    }

    /**
     * @Route("/conseil-cip/bid", name="cip_bid")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bidAction(Request $request)
    {
        $entityManager = $this->get('unilend.service.entity_manager');
        $cipManager    = $this->get('unilend.service.cip_manager');

        $rate   = $request->query->get('rate');
        $amount = $request->query->get('amount');

        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');

        if (
            false === $project->get($request->query->get('project'), 'slug')
            || empty($rate)
            || empty($amount)
        ) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Invalid parameters'
            ]);
        }

        /** @var \bids $bid */
        $bid                    = $entityManager->getRepository('bids');
        $bid->id_lender_account = $this->getLenderAccount()->id_lender_account;
        $bid->id_project        = $project->id_project;
        $bid->amount            = $amount * 100;
        $bid->rate              = $rate;

        $validation = $cipManager->isCIPValidationNeeded($bid);

        if ($validation) {
            // @todo log
        }

        return new JsonResponse([
            'validation' => $validation
        ]);
    }

    /**
     * @return \lenders_accounts
     */
    private function getLenderAccount()
    {
        /** @var UserLender $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();

        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lenderAccount->get($clientId, 'id_client_owner');

        return $lenderAccount;
    }
}
