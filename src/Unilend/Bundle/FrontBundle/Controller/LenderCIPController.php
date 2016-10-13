<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
        $cipManager    = $this->get('unilend.service.cip_manager');
        $entityManager = $this->get('unilend.service.entity_manager');
        $template      = [];

        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startQuestionnaireAction()
    {
        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

        $cipManager = $this->get('unilend.service.cip_manager');
        $evaluation = $cipManager->getCurrentEvaluation($lender);

        if (null !== $evaluation && count($cipManager->getAnswers($lender)) > 0) {
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
        $entityManager = $this->get('unilend.service.entity_manager');
        $cipManager    = $this->get('unilend.service.cip_manager');
        $template      = [
            'total_steps' => self::TOTAL_QUESTIONNAIRE_STEPS
        ];

        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

        $evaluation = $cipManager->getCurrentEvaluation($lender);

        if (null === $evaluation) {
            $cipManager->startEvaluation($lender);
        }

        $question = $cipManager->getLastQuestion($lender);

        if (null === $question) {
            $advices = implode("\n", $cipManager->getAdvices($lender));
            $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_ADVICE, $advices);
            $template['advices'] = $advices;
            return $this->render('lender_cip/advice.html.twig', $template);
        } else {
            $template['current_step'] = $question->order + 1;
            $template['answers']      = $cipManager->getAnswersByType($lender);
            $template['question']     = [
                'id'   => $question->id_lender_questionnaire_question,
                'type' => $question->type
            ];

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
        $questionId  = $request->request->get('question');
        $answerValue = $request->request->get('answer');

        if (null === $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $entityManager = $this->get('unilend.service.entity_manager');

        if (null === $answerValue) {
            /** @var \lender_questionnaire_question $question */
            $question = $entityManager->getRepository('lender_questionnaire_question');
            $question->get(\lender_questionnaire_question::TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE, 'type');

            if ($questionId != $question->id_lender_questionnaire_question) {
                // @todo error message
                return $this->redirectToRoute('cip_continue_questionnaire');
            }
        }

        $cipManager = $this->get('unilend.service.cip_manager');

        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

        $evaluation = $cipManager->getCurrentEvaluation($lender);

        if (null === $evaluation) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $question = $cipManager->getLastQuestion($lender);

        if ($question->id_lender_questionnaire_question != $questionId) {
            // @todo error message
            return $this->redirectToRoute('cip_continue_questionnaire');
        }

        $cipManager->saveAnswer($question, $evaluation, $answerValue);

        return $this->redirectToRoute('cip_continue_questionnaire');
    }

    /**
     * @Route("/conseil-cip/valider", name="cip_validate_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function validateQuestionnaireAction()
    {
        $entityManager = $this->get('unilend.service.entity_manager');
        $cipManager    = $this->get('unilend.service.cip_manager');

        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

        $cipManager->validateEvaluation($lender);

        return $this->redirectToRoute('cip_index');
    }

    /**
     * @Route("/conseil-cip/reset", name="cip_reset_questionnaire")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetQuestionnaireAction()
    {
        $entityManager = $this->get('unilend.service.entity_manager');
        $cipManager    = $this->get('unilend.service.cip_manager');

        /** @var UserLender $user */
        $user = $this->getUser();

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lender->get($user->getClientId());

        $cipManager->endCurrentEvaluation($lender);

        return $this->redirectToRoute('cip_start_questionnaire');
    }
}
