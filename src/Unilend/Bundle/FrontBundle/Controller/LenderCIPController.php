<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
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
        $template   = [
            'isCIPActive' => true
        ];

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

        if (null !== $evaluation && count($cipManager->getAnswersByType($evaluation)) > 0) {
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        if (null !== $request->query->get('start')) {
            $cipManager->startEvaluation($lender);
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        return $this->render('lender_cip/start.html.twig', [
            'isCIPActive'  => true,
            'current_step' => 1,
            'total_steps'  => self::TOTAL_QUESTIONNAIRE_STEPS
        ]);
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
        $template = [
            'isCIPActive' => true,
            'total_steps' => self::TOTAL_QUESTIONNAIRE_STEPS
        ];

        if (null === $evaluation) {
            return $this->redirectToRoute('cip_start_questionnaire');
        }

        $nextQuestion = $cipManager->getNextQuestion($evaluation);

        if (null === $nextQuestion) {
            $advices = implode("\n", $cipManager->getAdvices($lender));

            $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_ADVICE, $advices);

            $template['advices']         = $advices;
            $template['validEvaluation'] = $cipManager->isValidEvaluation($evaluation);
            return $this->render('lender_cip/advice.html.twig', $template);
        } else {
            $question = $cipManager->getLastQuestion($evaluation);
            $answers  = $cipManager->getAnswersByType($evaluation);
            $answer   = $answers[$question->type];

            $template['current_step'] = $question->order + 1;
            $template['answers']      = $answers;
            $template['question']     = [
                'id'   => $question->id_lender_questionnaire_question,
                'type' => $question->type,
            ];

            if ('' !== $answer['first_answer']) {
                $template['answers']['current']['first'] = $answer['first_answer'];
            }

            if ('' !== $answer['second_answer']) {
                $template['answers']['current']['second'] = $answer['second_answer'];
            }

            if ($question->isBooleanType($question->type)) {
                if (\lender_questionnaire_question::TYPE_AWARE_DIVIDE_INVESTMENTS === $question->type) {
                    $template['question']['valid_answer']   = \lender_questionnaire_question::VALUE_BOOLEAN_FALSE;
                    $template['question']['invalid_answer'] = \lender_questionnaire_question::VALUE_BOOLEAN_TRUE;
                } else {
                    $template['question']['valid_answer']   = \lender_questionnaire_question::VALUE_BOOLEAN_TRUE;
                    $template['question']['invalid_answer'] = \lender_questionnaire_question::VALUE_BOOLEAN_FALSE;
                }
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
        $cipManager    = $this->get('unilend.service.cip_manager');
        $lender        = $this->getLenderAccount();
        $evaluation    = $cipManager->getCurrentEvaluation($lender);

        if (null === $evaluation) {
            // @todo error message
            return $this->redirectToRoute('cip_start_questionnaire');
        }

        if ('values' === $request->query->get('reset')) {
            $cipManager->resetValues($evaluation);
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $questionId = $request->request->get('question');

        if (null === $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $question = $cipManager->getLastQuestion($evaluation);

        if ($question->id_lender_questionnaire_question != $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $answerValue   = $request->request->get('answer');
        $continueValue = $request->request->get('continue');

        if (null !== $continueValue) {
            switch ($continueValue) {
                case 'next_question':
                    $cipManager->insertQuestion($evaluation, $cipManager->getNextQuestion($evaluation));
                    return $this->redirectToRoute('cip_continue_questionnaire');
                case 'loop':
                    $answer = $cipManager->getAnswer($evaluation, $question);
                    if (null !== $answer) {
                        $answer->status = \lender_evaluation_answer::STATUS_INACTIVE;
                        $answer->update();
                    }
                    $cipManager->insertQuestion($evaluation, $question);
                    return $this->redirectToRoute('cip_continue_questionnaire');
                case 'back':
                    $bid = $request->getSession()->getFlashBag()->peek('cipBid')[0];

                    if (false === empty($bid['project'])) {
                        /** @var \projects $project */
                        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

                        if (is_numeric($bid['project']) && $project->get($bid['project'])) {
                            return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
                        }

                        $request->getSession()->getFlashBag()->remove('cipBid');
                    }

                    return $this->redirectToRoute('cip_index');
            }
        } else {
            if (null === $answerValue) {
                /** @var \lender_questionnaire_question $questionEntity */
                $questionEntity = $this->get('unilend.service.entity_manager')->getRepository('lender_questionnaire_question');
                $questionEntity->get(\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE, 'type');

                if ($questionId != $questionEntity->id_lender_questionnaire_question) {
                    // @todo error message
                    return $this->redirectToRoute('cip_continue_questionnaire');
                }
            }

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
        $cipManager->validateLenderEvaluation($this->getLenderAccount());

        $bid = $request->getSession()->getFlashBag()->peek('cipBid');

        if (false === empty($bid[0]['project'])) {
            /** @var \projects $project */
            $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

            if (is_numeric($bid[0]['project']) && $project->get($bid[0]['project'])) {
                return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
            }

            $request->getSession()->getFlashBag()->remove('cipBid');
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
        $cipManager->endLenderEvaluation($this->getLenderAccount());

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

        $this->addFlash('cipBid', ['amount' => $amount, 'rate' => $rate, 'project' => $project->id_project]);

        $response   = [];
        $lender     = $this->getLenderAccount();
        $evaluation = $cipManager->getCurrentEvaluation($lender);

        /** @var \bids $bid */
        $bid                    = $entityManager->getRepository('bids');
        $bid->id_lender_account = $lender->id_lender_account;
        $bid->id_project        = $project->id_project;
        $bid->amount            = $amount * 100;
        $bid->rate              = $rate;

        $validationNeeded = $cipManager->isCIPValidationNeeded($bid);
        $response['validation']  = $validationNeeded;

        if ($validationNeeded && null !== $evaluation && $cipManager->isValidEvaluation($evaluation)) {
            $advices    = [];
            $indicators = $cipManager->getIndicators($lender);

            if (null !== $indicators[CIPManager::INDICATOR_TOTAL_AMOUNT]) {
                /** @var \bids $bids */
                $bids        = $entityManager->getRepository('bids');
                $totalAmount = $bids->sum('id_lender_account = ' . $lender->id_lender_account . ' AND status IN (' . \bids::STATUS_BID_PENDING . ', ' . \bids::STATUS_BID_ACCEPTED . ', ' . \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY . ')', 'ROUND(amount / 100)');

                if ($totalAmount > $indicators[CIPManager::INDICATOR_TOTAL_AMOUNT]) {
                    $advices[CIPManager::INDICATOR_TOTAL_AMOUNT] = true;
                }
            }

            if (null !== $indicators[CIPManager::INDICATOR_AMOUNT_BY_MONTH]) {
                /** @var \bids $bids */
                $bids        = $entityManager->getRepository('bids');
                $totalAmount = $bids->sum('id_lender_account = ' . $lender->id_lender_account . ' AND added >= DATE_SUB(NOW(), INTERVAL 1 MONTH) AND status IN (' . \bids::STATUS_BID_PENDING . ', ' . \bids::STATUS_BID_ACCEPTED . ', ' . \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY . ')', 'ROUND(amount / 100)');

                if ($totalAmount > $indicators[CIPManager::INDICATOR_AMOUNT_BY_MONTH]) {
                    $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH] = true;
                }
            }

            if (null !== $indicators[CIPManager::INDICATOR_PROJECT_DURATION] && $project->period > $indicators[CIPManager::INDICATOR_PROJECT_DURATION]) {
                $advices[CIPManager::INDICATOR_PROJECT_DURATION] = true;
            }

            if (false === empty($advices)) {
                $message = '';

                if (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total, dépassement seuil mensuel, dépassement durée projet.';
                } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total, dépassement seuil mensuel.';
                } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total, dépassement durée projet.';
                } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total.';
                } elseif (isset($advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement total, dépassement durée projet.';
                } elseif (isset($advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement seuil mensuel.';
                } elseif (isset($advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                    $message = 'Attention, l’offre que vous venez de formuler ne correspond  pas à nos conseils  pour votre profil tel que vous l’avez renseigné (<a href="/conseil-cip">cliquez ici pour les relire</a>), car dépassement durée projet.';
                }

                $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_BID_ADVICE, strip_tags($message));
                $response['advices'] = $message;
            }
        } elseif ($validationNeeded) {
            $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_BID_EVALUATION_NEEDED);
            $response['questionnaire'] = true;
        }

        return new JsonResponse($response);
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
